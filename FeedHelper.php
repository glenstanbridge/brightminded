<?php

namespace App\Helpers;

use App\PageBuilder\Property;
use FtpClient\FtpClient;

class FeedHelper
{
    /**
     * @var array $xml
     */
    private $xml;

    /**
     * @throws \FtpClient\FtpException
     */
    public function __construct()
    {
        $ftp = new FtpClient();
        $ftp->connect(CALDES_HOST);
        $ftp->login(CALDES_USER, CALDES_PASSWORD);
        $ftp->pasv(true);
        $feed = $ftp->getContent(CALDES_FILE);

        $xml = simplexml_load_string($feed);
        if (!$xml) {
            //possibly email warning
            foreach (libxml_get_errors() as $error) {
                var_dump($error);
            }
            exit;
        }
        $this->xml = $xml;
    }

    /**
     * Parses the feed
     *
     * @return void
     */
    public function parse(): void
    {
        $i = 0;
        if ($this->xml->property->count() === 0) {
            return;
        }
        $updatedProperties = [];
        foreach ($this->xml->property as $xmlProperty) {
            $property = new Property();
            $property->findPropertyByRef($xmlProperty->property_ref);
            if (is_null($property->getId())) {
                $property->createProperty($xmlProperty->property_ref);
            }

            if ($this->updateProperty($property, $xmlProperty)) {
                $updatedProperties[] = $property->getId();
            }
        }

        if ($this->xml->property->count() === sizeof($updatedProperties)) {
            $this->disablePropertiesNotOnFeed($updatedProperties);
        }
    }

    /**
     * @param Property $property
     * @param \SimpleXMLElement $xmlProperty
     *
     * @return bool
     */
    private function updateProperty(Property $property, \SimpleXMLElement $xmlProperty): bool
    {
        $updatedStatus = true;
        $localities = [];
        foreach ($xmlProperty->localities->locality_name as $locality) {
            $localities[] = ['property_localities_locality' => (string)$locality];
        }
        $types = [];
        $groupTypes = [
            'A1' => 'Retail & Leisure',
            'A2' => 'Retail & Leisure',
            'A3' => 'Retail & Leisure',
            'Warehouse' => 'Warehouse / Industrial',
            'Industrial' => 'Warehouse / Industrial',
        ];

        foreach ($xmlProperty->property_types->type_description as $type) {
            $types[] = (string)$type;
            if (array_key_exists((string)$type, $groupTypes)) {
                $types[] = $groupTypes[(string)$type];
            }
        }
        $types = array_unique($types);
        $propertyTypes = [];
        foreach ($types as $type) {
            if ($type !== 'Warehouse' && $type !== 'Industrial') {
                $propertyTypes[] = ['property_types_type' => $type];
            }
        }
        $price = '';
        if ((string)$xmlProperty->for_sale === 'Y') {
            $price = (string)$xmlProperty->Freehold_price;
        }
        if ((string)$xmlProperty->to_let === 'Y') {
            $price = (string)$xmlProperty->asking_price;
        }
        $sqFt = (float)$xmlProperty->floor_area_min * 10.7639;
        $fieldMap = [
            'reference' => sanitize_title($xmlProperty->property_ref),
            'address' => (string)$xmlProperty->address1,
            'address2' => (string)$xmlProperty->address2,
            'address3' => (string)$xmlProperty->address3,
            'town' => (string)$xmlProperty->town,
            'county' => (string)$xmlProperty->region,
            'post_code' => (string)$xmlProperty->postcode,
            'latitude' => (string)$xmlProperty->latitude,
            'longitude' => (string)$xmlProperty->longitude,
            'localities' => $localities,
            'types' => $propertyTypes,
            'price' => $price,
            'floor_area' => (string)$xmlProperty->floor_area_min,
            'floor_area_sq_ft' => (int)$sqFt,
            'virtual_tour_url' => (string)$xmlProperty->virtual_tour_url,
            'tenure' => (string)$xmlProperty->tenure,
            'sale' => (string)$xmlProperty->for_sale === 'Y',
            'rent' => (string)$xmlProperty->to_let === 'Y',
            'status' => (string)$xmlProperty->property_status,
            'description' => (string)$xmlProperty->details,
            'location_description' => (string)$xmlProperty->brochurefield6,
            'legal_information' => (string)$xmlProperty->brochurefield2,
            'financial_information' => (string)$xmlProperty->brochurefield19,
            'rateable_value' => (string)$xmlProperty->brochurefield3,
            'accommodation_dimensions' => (string)$xmlProperty->brochurefield17,
            'feed_provided' => true,
        ];
        $property->updateFields($fieldMap);
        $_REQUEST['permalinks_customizer'] = $property->getAutomaticPermalink();
        $property->setTitle();
        unset($_REQUEST['permalinks_customizer']);

        if (!empty($xmlProperty->brochure_url)) {
            if (!$property->addBrochure($xmlProperty->brochure_url)) {
                $updatedStatus = false;
            }
        }

        //images
        if (!empty($xmlProperty->images)) {
            $images = [];
            foreach ($xmlProperty->images->image_src as $imgSrc) {
                $images[] = $imgSrc;
            }
            if (!$property->addFeaturedImage($images[0])) {
                $updatedStatus = false;
            }

            unset($images[0]);
            if (sizeof($images) > 0) {
                if (!$property->addImages($images)) {
                    $updatedStatus = false;
                }
            }
        }

        //floor plans
        if (!empty($xmlProperty->floorplans)) {
            $floorPlans = [];
            foreach ($xmlProperty->floorplans->floorplan_src as $floorPlanSrc) {
                if (strlen($floorPlanSrc) > 0) {
                    $floorPlans[] = $floorPlanSrc;
                }
            }
            if (sizeof($floorPlans) > 0) {
                if (!$property->addFloorPlans($floorPlans)) {
                    $updatedStatus = false;
                }
            }
        }

        return $updatedStatus;
    }

    /**
     * Uses a given array of updated property id's to determine which should be cleared up and deactivated after the feed has run
     *
     * @param array $activeProperties
     */
    private function disablePropertiesNotOnFeed(array $activeProperties): void
    {
        foreach (get_posts([
                'fields' => 'ids',
                'post_type' => 'property',
                'post_status' => 'publish',
                'numberposts' => -1,
                'post__not_in' => $activeProperties,
                'meta_key' => 'property_feed_provided',
                'meta_value' => 1,
            ]) as $propertyId
        ) {

            //remove attachements for this property
            foreach (
                get_posts([
                              'fields' => 'ids',
                              'post_type' => 'attachment',
                              'posts_per_page' => -1,
                              'post_parent' => $propertyId,
                          ]) as $attachementId
            ) {
                wp_delete_attachment($attachementId);
            }
            wp_delete_post($propertyId,true);
        }
    }
}
