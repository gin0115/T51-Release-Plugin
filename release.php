#!/usr/bin/php

<?php

/**
 * Create a release of a plugin from a git repo.
 * Can either push into a release branch, or create as a zip.
 */

class Team51_Release {

	protected $repo_url;

	protected $repo_branch = 'trunk';

	public function __construct() {
		$this->set_current_repo_url();
	}


	/**
	 * Sets the current repo url.
	 *
	 * @return void
	 */
	public function set_current_repo_url(): void {
		exec( 'git config --get remote.origin.url', $this->repo_url );
		var_dump( $this );
	}
}


// Autoload
new Team51_Release();
