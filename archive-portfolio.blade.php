@extends('layouts.app')

@section('content')
    @php
        $query = new WP_Query([
            'post_type' => 'page',
            'pagename' => 'portfolio',
            'post_status' => 'private',
            'numberposts' => 1,
        ]);
    @endphp
    @if ($query->have_posts())
        @while($query->have_posts()) @php $query->the_post() @endphp
            @php $defaultPortfolioId = get_field('default_portfolio', get_the_ID()) @endphp
            @include('partials.page-builder')
        @endwhile
        @php
            wp_reset_postdata();
        @endphp
    @endif
    <div class="segment white center">
        <div class="grid-container">
            @if (!have_posts())
                <div class="grid-x grid-all">
                    <div class="cell">
                        <p class="lead text-center">Sorry, no records found.</p>
                    </div>
                </div>
            @elseif ($cache = wp_cache_get('portfolio'))
              <portfolio
                :images="{{ collect($cache['images'])->toJson() }}"
                :projects="{{ collect($cache['projects'])->toJson() }}"
                :sectors="{{ collect($cache['sectors'])->toJson() }}"
                :default="{{ json_encode($cache['default']) }}"
                ></portfolio>
            @else
                  @php
                    $projects = [];
                    $images = [];
                    $sectors = [];
                    $default = null;
                  @endphp
                  @while (have_posts()) @php the_post() @endphp
                    @php
                      $portfolio = new \App\PageBuilder\Portfolio(get_the_ID());
                      $sectors = array_unique(array_merge($sectors, $portfolio->sectors()));
                      $projects[] = ['id' => get_the_ID(), 'name' => $portfolio->name()];
                      $images = array_merge($images, $portfolio->images());
                      if (get_the_ID() === $defaultPortfolioId) {
                          $default = $portfolio->name();
                      }
                    @endphp
                  @endwhile
                  @php wp_cache_set('portfolio', ['images' => $images, 'projects' => $projects, 'sectors' => $sectors, 'default' => $default]); @endphp
            <portfolio
              :images="{{ collect($images)->toJson() }}"
              :projects="{{ collect($projects)->toJson() }}"
              :sectors="{{ collect($sectors)->toJson() }}"
              :default="{{ json_encode($default) }}"
            ></portfolio>
            @endif
        </div>
    </div>
@endsection
