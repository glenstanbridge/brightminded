<template>
  <div class="portfolio">
    <div class="grid-x">
      <div class="content-cell cell small-12 large-9 project-filters">
        <h6>Filter by project</h6>
        <a class="button" v-for="(project, i) in projects" :key="i" @click="filterProject(toKebabCase(project.name))" v-bind:class="{'active' : projectFilter === toKebabCase(project.name)}">{{ project.name }}</a>
      </div>
      <div class="content-cell cell small-12 large-3 sector-filters">
        <h6>Filter by type</h6>
        <a class="button" v-for="(sector, i) in sectors" :key="i" @click="filterSector(sector)" v-bind:class="{'active' : sectorFilter === sector}">{{ sector }}</a>
      </div>
    </div>
    <div id="portfolio-grid" class="portfolio-grid" ref="portfolio-grid">
      <div class="grid-sizer"></div>
      <div class="gutter-sizer"></div>
      <a :class="['grid-item gallery-item', {
        'grid-item--width2' : image.ratio > 1.3,
        'grid-item--height2' : image.ratio < 0.8,
      }]" v-for="image in filterImages"
         :key="image.ID"
         :data-project="toKebabCase(image.project)"
         :data-sectors="image.sectors"
         :data-ratio="image.ratio"
         :data-external-thumb-image="image.gallery"
         :data-src="image.large"
         :href="image.large"
         :style="backgroundImage(image.gallery)"
      >
      </a>
    </div>
  </div>
</template>
<script>
    import  "lightgallery";
    import "lg-autoplay";
    import "lg-zoom";
    import "lg-thumbnail";
    import "lg-fullscreen";
    import Masonry from "masonry-layout";
    import "imagesloaded";
    export default {
        props: {
            images: {
                type: Array,
                required: true,
            },
            projects: {
              type: Array,
              required: true,
            },
            sectors: {
              type: Array,
              required: true,
            },
            default: {
              type: String,
              required: true,
            },
            options: {
                type: Object,
                required: false,
                default: () => {
                    return {
                      exThumbImage: "data-external-thumb-image",
                    }
                },
            },
        },
        data: () => ({
          projectFilter: "",
          sectorFilter: "",
          msnry: "",
          lg: document.getElementById("portfolio-grid"),
          imagesLoaded: "",
        }),
        mounted() {
          if (window.location.hash) {
            this.filterProject(window.location.hash.substr(1))
          } else {
            this.filterProject(this.toKebabCase(this.default));
          }
          this.msnry = new Masonry(".portfolio-grid", {
            itemSelector: ".grid-item",
            columnWidth: ".grid-sizer",
            percentPosition: true,
            gutter: ".gutter-sizer",
          });
          this.imagesLoaded = require("imagesloaded");
          this.imagesLoaded(".portfolio-grid", {background: ".gallery-item"}, this.updateMasonry());
        },
      computed: {
          filterImages() {
            if (this.projectFilter === "" && this.sectorFilter === "") return this.images;
            if (this.sectorFilter === "") return this.images.filter(i => this.toKebabCase(i.project) === this.projectFilter);
            else return this.images.filter(i => i.sectors.includes(this.sectorFilter));
          },
      },
      methods: {
        filterProject(project) {
          this.projectFilter = this.projectFilter === project ? "" : project;
          this.sectorFilter = "";
          this.updateMasonry();
        },
        filterSector(sector) {
          this.sectorFilter = this.sectorFilter === sector ? "" : sector;
          this.sectorFilter = sector;
          this.projectFilter = "";
          this.updateMasonry();
        },
        backgroundImage(imageUrl)  {
          return {
            "background-image": "url(" + imageUrl + ")",
          };
        },
        toKebabCase(str) {
          return str
            .match(/[A-Z]{2,}(?=[A-Z][a-z]+[0-9]*|\b)|[A-Z]?[a-z]+[0-9]*|[A-Z]|[0-9]+/g)
            .map(x => x.toLowerCase())
            .join('-');
        },
        updateMasonry() {
          this.$nextTick(function () {
            this.msnry.reloadItems();
            this.msnry.layout();
            this.$nextTick(function () {
                $("#portfolio-grid").lightGallery();
                $("#portfolio-grid").data('lightGallery').destroy(true);
                $("#portfolio-grid").lightGallery(
                  Object.assign({selector: ".gallery-item"}, this.options)
                );
            });
          });
        },
      },
    }
</script>
