<?php
/**
 * Returns schema Article data.
 */
class WPSEO_Schema_NewsArticle implements WPSEO_Graph_Piece {

	/**
	 * A value object with context variables.
	 *
	 * @var WPSEO_Schema_Context
	 */
	private $context;

	/**
	 * WP post object.
	 *
	 * @var WP_Post
	 */
	private $post;

	/**
	 * WPSEO_Schema_Article constructor.
	 *
	 * @param WPSEO_Schema_Context $context A value object with context variables.
	 */
	public function __construct( WPSEO_Schema_Context $context ) {
		$this->context = $context;
        $this->post = get_post( $context->id );
	}

	/**
	 * Determines whether or not a piece should be added to the graph.
	 *
	 * @return bool
	 */
	public function is_needed() {
		if ( is_singular() && 'post' == $this->post->post_type ) {
			return true;
		}

        return false;
	}

	/**
	 * Returns Article data.
	 *
	 * @return array $data Article data.
	 */
	public function generate() {
		$data          = array(
			'@type'            => 'NewsArticle',
			'@id'              => $this->context->canonical . '#NewsArticle',
			'mainEntityOfPage' => array( '@id' => $this->context->canonical . WPSEO_Schema_IDs::WEBPAGE_HASH ),
			'headline'         => trim( mb_substr( $this->post->post_title, 0, 110, 'UTF-8' ) ),
			'author'           => array( '@id' => WPSEO_Schema_Utils::get_user_schema_id( $this->post->post_author, $this->context ) ),
			'datePublished'    => mysql2date( DATE_W3C, $this->post->post_date_gmt, false ),
			'dateModified'     => mysql2date( DATE_W3C, $this->post->post_modified_gmt, false ),
            'description'      => $this->get_the_excerpt( $this->post ),
		);

		if( 'open' == $this->post->comment_status ) {
		    $comment_count = get_comment_count( $this->context->id );
		    $data['commentCount'] = $comment_count['approved'];
        }

		if ( $this->context->site_represents_reference ) {
			$data['publisher'] = $this->context->site_represents_reference;
		}

		$data = $this->add_image( $data );
		$data = $this->add_keywords( $data );
		$data = $this->add_sections( $data );

		return $data;
	}

	/**
	 * Adds tags as keywords, if tags are assigned.
	 *
	 * @param array $data Article data.
	 *
	 * @return array $data Article data.
	 */
	private function add_keywords( $data ) {
		/**
		 * Filter: 'wpseo_schema_article_keywords_taxonomy' - Allow changing the taxonomy used to assign keywords to a post type Article data.
		 *
		 * @api string $taxonomy The chosen taxonomy.
		 */
		$taxonomy = apply_filters( 'wpseo_schema_article_keywords_taxonomy', 'post_tag' );

		return $this->add_terms( $data, 'keywords', $taxonomy );
	}

	/**
	 * Adds categories as sections, if categories are assigned.
	 *
	 * @param array $data Article data.
	 *
	 * @return array $data Article data.
	 */
	private function add_sections( $data ) {
		/**
		 * Filter: 'wpseo_schema_article_sections_taxonomy' - Allow changing the taxonomy used to assign keywords to a post type Article data.
		 *
		 * @api string $taxonomy The chosen taxonomy.
		 */
		$taxonomy = apply_filters( 'wpseo_schema_article_sections_taxonomy', 'category' );

		return $this->add_terms( $data, 'articleSection', $taxonomy );
	}

	/**
	 * Adds a term or multiple terms, comma separated, to a field.
	 *
	 * @param array  $data     Article data.
	 * @param string $key      The key in data to save the terms in.
	 * @param string $taxonomy The taxonomy to retrieve the terms from.
	 *
	 * @return mixed array $data Article data.
	 */
	private function add_terms( $data, $key, $taxonomy ) {
		$terms = get_the_terms( $this->context->id, $taxonomy );
		if ( is_array( $terms ) ) {
			$keywords = array();
			foreach ( $terms as $term ) {
				// We are checking against the WordPress internal translation.
				// @codingStandardsIgnoreLine
				if ( $term->name !== __( 'Uncategorized' ) ) {
					$keywords[] = $term->name;
				}
			}
			$data[ $key ] = implode( ',', $keywords );
		}

		return $data;
	}

	/**
	 * Adds an image node if the post has a featured image.
	 *
	 * @param array $data The Article data.
	 *
	 * @return array $data The Article data.
	 */
	private function add_image( $data ) {
		if ( $this->context->has_image ) {
			$data['image'] = array(
				'@id' => $this->context->canonical . WPSEO_Schema_IDs::PRIMARY_IMAGE_HASH,
			);
		}

		return $data;
	}

    /**
     * Returns the post excerpt
     *
     * @param null|WP_Post $post WP Post object or null
     * @param int $trim_chars maximum excerpt length if needed
     * @param string $more append symbol
     *
     * @return string
     * @uses $wp_version
     * @uses excerpt_remove_blocks()
     * @uses strip_shortcodes()
     *
     */
    public function get_the_excerpt( $post, $trim_chars = 150, $more = '&hellip;' ) {
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return '';
        }

        $more_pos = mb_stripos( $post->post_content, '<!--more-->' );

        if ( $more_pos != false ) {
            $excerpt = mb_substr( $post->post_content, 0, $more_pos );
        } else {
            $excerpt = empty( $post->post_excerpt ) ? $post->post_content : $post->post_excerpt;
        }

        global $wp_version;
        if ( version_compare( $wp_version, '5.0', '>=' ) ) {
            $excerpt = excerpt_remove_blocks( strip_shortcodes( $excerpt ) );
        } else {
            $excerpt = strip_shortcodes( $excerpt );
        }

        $excerpt = trim( strip_tags( $excerpt ) );

        return mb_substr( $excerpt, 0, $trim_chars, 'UTF-8' ) . $more;
    }
}
