<?php

namespace App\PageBuilder;

use Illuminate\Support\Collection;

/**
 * Class Property
 * @package App\PageBuilder
 *
 * @property int $id
 */
class Property
{
    /**
     * @var null|int
     */
    private $id;

    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /**
     * Get a property by its reference value
     *
     * @param string $ref
     * @return void
     */
    public function findPropertyByRef(string $ref): void
    {
        $property = get_posts([
            'numberposts'	=> -1,
            'post_type'		=> 'property',
            'meta_key'		=> 'property_reference',
            'meta_value'	=> $ref,
            'post_status'   => 'any'
        ]);

        if (sizeof($property) === 1) {
            $this->id = $property[0]->ID;
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Programmatically create a new custom post type post for a property
     *
     * @string|null $ref
     * @return void
     */
    public function createProperty(?string $ref): void {
        if (is_null($ref) || strlen($ref) === 0) {
            return;
        }
        $this->id = wp_insert_post([
            'post_status' => 'publish',
            'post_date' => date('Y-m-d H:i:s'),
            'post_author' => 1,
            'post_type' => 'property',
            'post_name' => $ref,
            'post_category' => array(0)
        ]);
    }

    /**
     * Get featured image.
     *
     * @return string
     */
    public function featuredImage(): string
    {
        if (empty($image = get_field('property_featured_image', $this->id))) {
            return \App\asset_path("images/placeholders/property-placeholder.jpg");
        }

        return $image['sizes']['gallery'] ?: '';
    }

    /**
     * Get property images
     *
     * @return \Illuminate\Support\Collection
     */
    public function images(): Collection
    {
        $images = new Collection();
        collect(get_field('property_images', $this->id))->filter()->map(function ($item) use ($images) {
            $images->push($item);
        });

        return $images;
    }

    /**
     * Get floor plans
     *
     * @return \Illuminate\Support\Collection
     */
    public function floorPlans(): Collection
    {
        $floorPlans = new Collection();
        collect(get_field('property_floor_plans', $this->id))->filter()->map(function ($item) use ($floorPlans) {
            $floorPlans->push($item);
        });

        return $floorPlans;
    }

    /**
     * Property Reference
     *
     * @return string
     */
    public function reference(): string
    {
        return get_field("property_reference", $this->id) ?: '';
    }

    /**
     * Property for sale
     * @return bool
     */
    public function forSale(): bool
    {
        return get_field('property_sale', $this->id) ?: '';
    }

    /**
     * Property for let
     * @return bool
     */
    public function forLet(): bool
    {
        return get_field('property_rent', $this->id) ?: '';
    }

    /**
     * Address
     *
     * @return string
     */
    public function address(): string
    {
        return get_field('property_address', $this->id) ?: '';
    }

    /**
     * Address
     *
     * @return string
     */
    public function address2(): string
    {
        return get_field('property_address_2', $this->id) ?: '';
    }

    /**
     * Address
     *
     * @return string
     */
    public function address3(): string
    {
        return get_field('property_address_3', $this->id) ?: '';
    }

    /**
     * Town
     *
     * @return string
     */
    public function town(): string
    {
        return get_field('property_town', $this->id) ?: '';
    }

    /**
     * County
     *
     * @return string
     */
    public function county(): string
    {
        return get_field('property_county', $this->id) ?: '';
    }

    /**
     * Post code
     *
     * @return string
     */
    public function postCode(): string
    {
        return get_field('property_post_code', $this->id) ?: '';
    }

    /**
     * Locality
     *
     * @return string
     */
    public function locality(): string
    {
        return get_field('property_locality', $this->id) ?: '';
    }

    /**
     * Provides the address as two lines
     * @return string
     */
    public function twoLineAddress(): string
    {
        return $this->address() . "\n" . $this->town() . ' ' . $this->postCode();
    }
    /**
     * Price
     *
     * @return null|int
     */
    public function price(): ?int
    {
        return (int) get_field('property_price', $this->id) ?: null;
    }

    /**
     * Property name.
     *
     * @return string
     */
    public function name(): string
    {
        return get_the_title($this->id);
    }

    /**
     * Property status
     *
     * @return string
     */
    public function status(): string
    {
        $status = get_field('property_status', $this->id) ?: '';
        if (strtolower($status) === 'sold on web') {
            return 'Sold';
        }
        if (strtolower($status) === 'let on web') {
            return 'Let';
        }

        return $status;
    }

    /**
     * Property description
     *
     * @return string
     */
    public function description(): string
    {
        return get_field('property_description', $this->id) ?: '';
    }

    /**
     * Property floor area
     *
     * @return string
     */
    public function floorArea(): string
    {
        return get_field('property_floor_area', $this->id) ?: '';
    }

    /**
     * Property floor area in sq ft
     *
     * @return string
     */
    public function floorAreaSqFt(): string
    {
        return get_field('property_floor_area_sq_ft', $this->id) ?: '';
    }

    /**
     * Checks if lat/lng provided are actually coords, empty strings or zero as provided on feed
     *
     * @return bool
     */
    public function hasCoords(): bool
    {
        $lat = $this->getField('latitude');
        $lng = $this->getField('longitude');
        if (strlen($lat) === 0 || strlen($lng) === 0 || $lat === '0' || $lng === '0') {
            return false;
        }
        return true;
    }

    /**
     * Property coordinates
     *
     * @return string
     */
    public function coords(): string
    {
        return collect([
            'lat' => (float) $this->getField('latitude') ?: '',
            'lng' => (float) $this->getField('longitude') ?: ''
        ])->toJson();
    }

    /**
     * Property coordinates
     *
     * @return string
     */
    public function marker(): string
    {
        $marker = new Collection();
        $marker->push([
            'lat' => (float) get_field('property_latitude', $this->id) ?: '',
            'lng' => (float) get_field('property_longitude', $this->id) ?: '',
            'popup' => true,
            'text' => $this->address() . ', ' . $this->address2() . ' ' . $this->address3() . ' ' . $this->town() . ' ' . $this->postCode(),
        ]);
        return $marker->toJson();
    }

    /**
     * Property brochure
     *
     * @return string
     */
    public function brochureUrl(): string
    {
        return get_field('property_brochure', $this->id) ?: '';
    }

    /**
     * Property virtual tour
     *
     * @return string
     */
    public function virtualTourUrl(): string
    {
        return get_field('property_virtual_tour_url', $this->id) ?: '';
    }

    /**
     * Get property types
     *
     * @return \Illuminate\Support\Collection
     */
    public function propertyTypes(): Collection
    {
        $types = new Collection();
        collect(get_field('property_types', $this->id))->filter()->map(function ($item) use ($types) {
            $types->push($item['property_types_type']);
        });

        return $types->sort();
    }


    /**
     * Get the url of the property
     *
     * @return string
     */
    public function url(): string
    {
        return get_permalink($this->id);
    }

    /**
     * Get property features
     *
     * @return \Illuminate\Support\Collection
     */
    public function features(): Collection
    {
        $features = new Collection();
        collect($this->getField('features'))->filter()->map(function ($item) use ($features) {
            $features->push($item['property_feature']);
        });

        if ($features->count() > 0) {
            return $features;
        }
        if ($this->floorArea() > 0) {
            $features->push(
                $this->floorAreaSqFt()
                . "sq. ft. / "
                . number_format($this->floorArea(), 2)
                . 'm<sup>2</sup>'
            );
        }

        $features->push($this->propertyTypes()->implode(', '));
        $features->push($this->getField('tenure'));
        $features->push($this->town());

        return $features;
    }

    /**
     * Creates a label for the status of the property
     *
     * @return string
     */
    public function statusLabel(): string
    {
       $allowedStatus = ['Sold' , 'Under Offer', 'Let'];
       if (in_array($this->status(), $allowedStatus)) {
            return $this->status();
       }

       return '';
    }

    /**
     * Provides content for the given field key in a more visually appealing format
     * Splits into paragraphs and detects any block capital texts and turns into headings
     *
     * @param string $key
     * @param string $initialTitle
     * @return array
     */
    public function filterContentBlock(string $key, string $initialTitle): string {
        $ignoreTitles = [
            'IMPERIALMETRIC',
            'IMPERIAL METRIC',
            'W.C'
        ];
        $keys = explode(',', $key);
        $contentParts = [];
        foreach ($keys as $k) {
            $contentParts[] = $this->getField($k);
        }
        $content = implode('<br />', $contentParts);
        $formattedContent = '';
        if (strlen($content) === 0) {
            return '';
        }
        $content = str_replace(['<br>', '<br/>', '<br /><br />'], '<br />', $content);
        $contentParts = explode('<br />', $content);
        if (sizeof($contentParts) === 1) {
           return $this->formatContentHeading($initialTitle) . '<p>' . $contentParts[0] . '</p>';
        }


        foreach ($contentParts as $k => $part) {
            $part = trim($part);
            $formattedContent .= (strtoupper($part) === $part && !in_array($part, $ignoreTitles))
                ? $this->formatContentHeading($part)
                : (($k === 0 ? $this->formatContentHeading($initialTitle) : '') . ((!in_array($part, $ignoreTitles) || $part === 'W.C') ? '<p>' . $part . '</p>' : ''));
        }

        return $formattedContent;
    }

    private function formatContentHeading(string $text): string {
        $headingTag = '<h3>&&</h3>';
        return str_replace(['&&', ':'], [ucwords(strtolower($text)), ''], $headingTag);
    }

    /**
     * @param string $key
     * @param string $value
     */
    private function updateField(string $key, string $value): void
    {
        update_field('property_' . $key, $value, $this->id);
    }

    /**
     * @param string $key
     * @param array $value
     */
    private function updateFieldArray(string $key, array $value): void
    {
        update_field('property_' . $key, $value, $this->id);
    }

    /**
     * @param array $fields
     */
    public function updateFields(array $fields): void
    {
        foreach ($fields as $key => $value) {
            (is_array($value)) ? $this->updateFieldArray($key, $value) : $this->updateField($key, $value);
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getField(string $key)
    {
        return get_field('property_' . $key, $this->id);
    }

    /**
     * Handles transfer and attachment of a file to the property
     *
     * @param string $attachmentUrl
     * @return int|null $attachmentId
     */
    private function addAttachment(string $attachmentUrl): ?int
    {
        $uploadFileContents = @file_get_contents($attachmentUrl);
        if ($uploadFileContents) {
            $uploadFile = wp_upload_bits($this->id . '-' . basename($attachmentUrl), null, $uploadFileContents);

            if (!$uploadFile['error']) {
                $wpFiletype = wp_check_filetype($this->id . '-' . basename($attachmentUrl), null);
                $attachment = array(
                    'post_mime_type' => $wpFiletype['type'],
                    'post_parent' => $this->id,
                    'post_title' => preg_replace('/\.[^.]+$/', '', $this->id . '-' . basename($attachmentUrl)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attachmentId = wp_insert_attachment($attachment, $uploadFile['file'], $this->id);
                if (!is_wp_error($attachmentId)) {
                    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                    $attachmentData = wp_generate_attachment_metadata($attachmentId, $uploadFile['file']);
                    wp_update_attachment_metadata($attachmentId, $attachmentData);
                    return $attachmentId;
                }
            }
        }

        return null;
    }

    /**
     * Detect if property already has the given pdf and delegates the handling of it
     *
     * @param string $brochureUrl
     * @return bool
     */
    public function addBrochure(string $brochureUrl): bool
    {
        $filename = $this->id . '-' . basename($brochureUrl);
        // check if already uploaded
        $attachedBrochureFilename = basename($this->getField('brochure'));
        if ($attachedBrochureFilename === $filename) {
            return true;
        }
        $attachmentId = $this->addAttachment($brochureUrl);
        if (!is_null($attachmentId)) {
            $this->updateField('brochure', $attachmentId);
            return true;
        }

        return false;
    }

    /**
     * Detect if property already has the provided floor plans and delegates the handling of them
     *
     * @param array $floorPlans
     * @return bool
     */
    public function addFloorPlans(array $floorPlans): bool {
        $currentFloorPlans = $this->getField('floor_plans');
        $currentFloorPlanFiles = [];
        $currentFloorPlanIds = [];
        if (!is_null($currentFloorPlans)) {
            foreach ($currentFloorPlans as $k => $currentFloorPlan) {
                $currentFloorPlanFiles[$k] = basename($currentFloorPlan['plan']);
                $currentFloorPlanIds[$k] = attachment_url_to_postid($currentFloorPlan['plan']);
            }
        }

        $newFloorPlans = [];
        foreach ($floorPlans as $floorPlanUrl) {
            $floorPlanFilename = $this->id . '-' . basename($floorPlanUrl);
            if (in_array($floorPlanFilename, $currentFloorPlanFiles)) {
                $newFloorPlans[] = [
                    'plan' => $currentFloorPlanIds[array_search($floorPlanFilename, $currentFloorPlanFiles)]
                ];
            } else {
                $newUploadedFloorPlanAttachmentId = $this->addAttachment($floorPlanUrl);
                if (!is_null($newUploadedFloorPlanAttachmentId)) {
                    $newFloorPlans[] = ['plan' => $newUploadedFloorPlanAttachmentId];
                }
            }
        }
        $this->updateFieldArray('floor_plans', $newFloorPlans);
        return (sizeof($newFloorPlans) === sizeof($floorPlans));
    }

    /**
     * Detect if property already has the property featured image and delegates the handling of it
     *
     * @param string $imgUrl
     * @return bool
     */
    public function addFeaturedImage(string $imgUrl): bool {
        $filename = $this->id . '-' . basename($imgUrl);
        $fileTitle = preg_replace('/\.[^.]+$/', '', $filename);
        $attachedFeaturedImage = $this->getField('featured_image');
        if (!empty($attachedFeaturedImage)) {
            $attachedFeaturedFileTitle = $attachedFeaturedImage['title'];
        }

        if (empty($attachedFeaturedImage) || $attachedFeaturedFileTitle !== $fileTitle) {
            $attachmentId = $this->addAttachment($imgUrl);
            if (!is_null($attachmentId)) {
                $this->updateField('featured_image', $attachmentId);
                return true;
            }
        }

        if ($attachedFeaturedFileTitle === $fileTitle) {
            return true;
        }

        return false;
    }

    /**
     * Detect if property already has the provided images and delegates the handling of them
     *
     * @param array $images
     * @return bool
     */
    public function addImages(array $images): bool {
        $currentPropertyImages = $this->getField('images');
        $currentPropertyImagesFileTitles = [];
        $currentPropertyImagesAttachmentIds = [];
        if (!is_null($currentPropertyImages)) {
            foreach ($currentPropertyImages as $k => $currentImage) {
                $currentPropertyImagesFileTitles[$k] = $currentImage['image']['title'];
                $currentPropertyImagesAttachmentIds[$k] = $currentImage['image']['id'];
            }
        }

        $newPropertyImages = [];
        foreach ($images as $imageUrl) {
            $imageFilename = $this->id . '-' . basename($imageUrl);
            $imageTitle = preg_replace('/\.[^.]+$/', '', $imageFilename);
            if (in_array($imageTitle, $currentPropertyImagesFileTitles)) {
                $newPropertyImages[] = [
                    'image' => $currentPropertyImagesAttachmentIds[array_search($imageTitle, $currentPropertyImagesFileTitles)]
                ];
            } else {
                $newUploadedImageAttachmentId = $this->addAttachment($imageUrl);
                if (!is_null($newUploadedImageAttachmentId)) {
                    $newPropertyImages[] = ['image' => $newUploadedImageAttachmentId];
                }
            }
        }

        $this->updateFieldArray('images', $newPropertyImages);
        return (sizeof($newPropertyImages) === sizeof($images));
    }

    /**
     * Creates the page title for the property and sets it
     */
    public function setTitle(): void
    {
        $title = 'Commercial property';
        if ($this->getField('sale')) {
            $title .= ' for sale';
        }

        if ($this->getField('rent')) {
            $title .= ' to let';
        }

        $title .= ' in '
            . $this->getField('town')
            . ' '
            . $this->getField('county')
            . ' | Ref: '
            . $this->reference()
            .  ' | Carr & Priddle';
        wp_update_post([
            'ID' => $this->id,
            'post_title' => $title
        ]);

    }

    /**
     * Creates the permalink
     * @return string
     */
    public function getAutomaticPermalink(): string {
        return 'commercial-property-'
            . ($this->forSale() ? 'for-sale' : 'to-let')
            . '/'
            . sanitize_title($this->town())
            . '/'
            . $this->reference()
            . '/';
    }
}
