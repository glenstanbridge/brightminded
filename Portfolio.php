<?php

namespace App\PageBuilder;

use Illuminate\Support\Collection;

/**
 * Class Portfolio
 *
 * @package App\Traits\PageBuilder
 *
 * @property int $id
 */
class Portfolio
{

    /**
     * Portfolio id.
     *
     * @var int
     */
    protected $id;

    /**
     * Portfolio constructor.
     *
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * Get portfolio name.
     *
     * @return string
     */
    public function name(): string
    {
        return get_the_title($this->id);
    }



    /**
     * Get the images
     *
     * @return array
     */
    public function images(): array
    {
        $images = [];
        foreach (get_field('portfolio_images', $this->id) as $item) {
            $images[] = [
                'gallery'   => $item['portfolio_images_image']['sizes']['medium'],
                'ratio'     => $item['portfolio_images_image']['sizes']['portfolio-width'] / $item['portfolio_images_image']['sizes']['portfolio-height'],
                'large'     => $item['portfolio_images_image']['sizes']['large'],
                'project'   => $this->name(),
                'sectors'   => $this->sectors()
            ];
        }

        return $images;
    }

    /**
     * Get the sectors.
     *
     * @return array
     */
    public function sectors(): array
    {
        $sectors = [];
        foreach (get_field('portfolio_sector', $this->id) as $item) {
            $sectors[] = $item->name;
        }

        return $sectors;
    }
}
