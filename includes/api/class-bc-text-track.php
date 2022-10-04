<?php
/**
 * BC_Text_Track class file.
 *
 * @package Brightcove_Video_Connect
 */

/**
 * Represent an individual text track (i.e. caption track) for a video
 */
class BC_Text_Track {
	/**
	 * URL for a WebVTT file
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * ISO 639 2-letter language code for the text tracks
	 *
	 * @var string
	 */
	protected $src_lang;

	/**
	 * How the VTT file is meant to be used
	 *
	 * @var string
	 */
	protected $kind;

	/**
	 * Human-readable title
	 *
	 * @var string
	 */
	protected $label;

	/**
	 * Default language for captions/subtitles
	 *
	 * @var bool
	 */
	protected $default;

	/**
	 * Mime type for the text track
	 *
	 * @var string
	 */
	protected $mime_type = 'text/webvtt';

	/**
	 * Build up the object as needed.
	 *
	 * @param string $url URL for a WebVTT file
	 * @param string $language ISO 639 2-letter language code for the text tracks
	 * @param string $kind How the VTT file is meant to be used
	 * @param string $label Human-readable title
	 * @param bool   $default  Set the default language for captions/subtitles
	 */
	public function __construct( $url, $language = 'en-US', $kind = 'captions', $label = '', $default = false ) {
		$this->url      = esc_url_raw( $url );
		$this->src_lang = sanitize_text_field( $language );
		if ( ! in_array( $kind, array( 'captions', 'subtitles', 'descriptions', 'chapters', 'metadata' ), true ) ) {
			$this->kind = 'captions';
		} else {
			$this->kind = $kind;
		}
		$this->label   = sanitize_text_field( $label );
		$this->default = (bool) $default;
	}

	/**
	 * Return an array representation of the Text Track for use in API ingest requests
	 *
	 * @return array
	 */
	public function to_array() {
		$data = array(
			'url'      => $this->url,
			'src_lang' => $this->src_lang,
			'kind'     => $this->kind,
			'default'  => $this->default,
		);

		if ( ! empty( $this->label ) ) {
			$data['label'] = $this->label;
		}

		return $data;
	}

	/**
	 * Return an array representation of a Text Track for use in API PATCH requests
	 *
	 * @return array Data to submit.
	 */
	public function to_array_patch() {
		$data = array(
			'src'       => $this->url,
			'src_lang'  => $this->src_lang,
			'kind'      => $this->kind,
			'default'   => $this->default,
			'mime_type' => $this->mime_type,
		);

		if ( ! empty( $this->label ) ) {
			$data['label'] = $this->label;
		}

		return $data;
	}
}
