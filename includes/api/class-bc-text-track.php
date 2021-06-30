<?php

/**
 * Represent an individual text track (i.e. caption track) for a video
 */

class BC_Text_Track {
	/**
	 * @var string URL for a WebVTT file
	 */
	protected $url;

	/**
	 * @var string ISO 639 2-letter language code for the text tracks
	 */
	protected $srcLang;

	/**
	 * @var string How the VTT file is meant to be used
	 */
	protected $kind;

	/**
	 * @var string Human-readable title
	 */
	protected $label;

	/**
	 * @var bool Set the default language for captions/subtitles
	 */
	protected $default;

	/**
	 * @var string Set the default Mime Type for captions
	 */
	protected $mimeType = 'text/webvtt';

	/**
	 * Build up the object as needed.
	 *
	 * @param string   $url
	 * @param string   $language
	 * @param string [ $kind]
	 * @param string [ $label]
	 * @param bool   [ $default]
	 */
	public function __construct( $url, $language = 'en-US', $kind = 'captions', $label = '', $default = false ) {
		$this->url     = esc_url_raw( $url );
		$this->srcLang = sanitize_text_field( $language );
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
	public function toArray() {
		$data = array(
			'url'     => $this->url,
			'srclang' => $this->srcLang,
			'kind'    => $this->kind,
			'default' => $this->default,
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
	public function toArrayPatch() {
		$data = array(
			'src'       => $this->url,
			'srclang'   => $this->srcLang,
			'kind'      => $this->kind,
			'default'   => $this->default,
			'mime_type' => $this->mimeType,
		);

		if ( ! empty( $this->label ) ) {
			$data['label'] = $this->label;
		}

		return $data;
	}
}
