#!/usr/bin/php

<?php

/**
 * Create a release of a plugin from a git repo.
 * Can either push into a release branch, or create as a zip.
 */

class Team51_Release {

	/**
	 * Sets the remote path
	 *
	 * @var string
	 */
	protected $repo_url;

	/**
	 * The main branch to use as the base.
	 *
	 * @var string
	 */
	protected $source_branch = 'trunk';

	/**
	 * The main branch to use as the base.
	 *
	 * @var string
	 */
	protected $destination_branch = 'release';

	/**
	 * Does remote branch exist
	 *
	 * @param array{remote:bool,local:bool} $branch
	 */
	protected $desination_branch_exists = array(
		'remote' => false,
		'local'  => false,
	);

	public function __construct( string $source_branch, string $destination_branch ) {
		$this->source_branch      = $source_branch;
		$this->destination_branch = $destination_branch;
	}

	/**
	 * Runs the intialisation scripts.
	 *
	 * @return self
	 */
	public function init(): self {
		// Checkout to current source branch.
		$this->git_checkout( $this->source_branch );
		$this->set_current_repo_url();
		$this->desination_branches_exists();
		$this->maybe_create_destination_branches();

		// Merge source to destination.
		$this->merge_destination_with_source();
		$this->run_build();

		return $this;
	}


	/**
	 * Sets the current repo url.
	 *
	 * @return void
	 */
	public function set_current_repo_url(): void {

		self::output( '** Fetching remote path' );

		exec( 'git config --get remote.origin.url', $this->repo_url );

		// Check we have a valid path.
		if ( $this->repo_url === null || $this->repo_url === '' ) {
			$this->abort( '!! Failed to fetch remote path' );
		}
		self::output( '** Found remote path' );
	}

	/**
	 * Checks if the remote and local versions of the release branch exist.
	 *
	 * @return void
	 */
	public function desination_branches_exists(): void {
		self::output( '** Checking release branch status.' );

		// Remote.
		$remote_branch = null;
		exec(
			sprintf( 'git ls-remote --heads %s %s', $this->repo_url[0], $this->destination_branch ),
			$remote_branch
		);
		$this->desination_branch_exists['remote'] = count( $remote_branch ) > 0;

		// Local
		$local_branch = null;
		exec(
			sprintf( 'git show-ref refs/heads/ %s', $this->destination_branch ),
			$local_branch
		);
		$this->desination_branch_exists['local'] = count( $local_branch ) > 0;

		self::output( sprintf( '** -- Remote branch %s exists', $this->desination_branch_exists['remote'] ? 'does' : 'does not' ) );
		self::output( sprintf( '** -- Local branch %s exists', $this->desination_branch_exists['local'] ? 'does' : 'does not' ) );

	}

	/**
	 * Checks if the local destination branch exists.
	 *
	 * @return void
	 */
	public function maybe_create_destination_branches(): void {
		if ( $this->desination_branch_exists['local'] === false ) {
			$result = array();
			exec( sprintf( 'git checkout -b %s', $this->destination_branch ), $result );
			self::output( sprintf( '** Creating local %s branch......', $this->destination_branch ) );
		}
	}

	/**
	 * Merges the source branch into release branch.
	 *
	 * @return void
	 */
	public function merge_destination_with_source(): void {
		$this->git_checkout( $this->destination_branch );

		$merge_result = array();
		exec(
			sprintf( 'git merge %s', $this->source_branch ),
			$merge_result
		);

		if ( count( $merge_result ) > 0 ) {
			self::output( sprintf( '** Successfully merged %s into %s ', $this->source_branch, $this->destination_branch ) );
			self::output( '** -- ' . $merge_result[0] );
		} else {
			$this->abort( sprintf( '!! Failed to merge %s into %s ', $this->source_branch, $this->destination_branch ) );
		}
	}

	/**
	 * Runs the build process.
	 *
	 * @return void
	 */
	public function run_build(): void {
		self::output( PHP_EOL . '** RUNNING BUILD ' . PHP_EOL );

		// Run composer production.
		shell_exec( 'composer update --no-dev' );

		// Run npm production.
		shell_exec( 'rm -r node_modules && npm install --production' );
	}

	/**
	 * Push the current destination branch to origin.
	 *
	 * @return void
	 */
	public function push_destination(): void {
		if ( $this->desination_branch_exists['remote'] === false ) {
			shell_exec( sprintf( 'git push -u origin %s', $this->destination_branch ) );
		} else {
			shell_exec( 'git push -u origin' );
		}
	}

	public function tag_release( string $tag ) {
		// Check if that tag already exist.
		$current_tags = $this->get_current_repo_tags();
		if ( in_array( $tag, $current_tags ) ) {
			foreach ( $current_tags as $existing_tag ) {
				self::output( "!! Tag: {$existing_tag}" );
			}

			self::abort( "!! Tag {$tag} already exists, please try again with a later version" );
		}

		$result = array();

		exec( 'git add -A', $result );
		exec( 'git commit -m "Pushed from Team 51 auto publish script with tag"', $result );
		exec( 'git push', $result );
		exec( sprintf( 'git tag -a %s -m "%s"', $tag, $tag ), $result );
		exec( 'git push --tags origin master', $result );

	}

	public function get_current_repo_tags(): array {
		$tags = array();
		exec( 'git fetch --all && git tag', $tags );
		return array_filter(
			$tags,
			function( $e ) {
				return $e !== 'Fetching origin';
			}
		);
	}

	/**
	 * Checks out the defined branch.
	 *
	 * @param string $branch
	 * @return void
	 */
	public function git_checkout( string $branch ): void {

		// Check if already on requested branch.
		$current_branch = null;
		exec(
			'git branch --show-current',
			$current_branch
		);
		// Bail if we are.
		if ( is_array( $current_branch ) && $current_branch[0] === $branch ) {
			self::output( '** Currently checkouted out to ' . $branch );
			return;
		}

		$result = array();
		exec(
			sprintf( 'git checkout %s ', $branch ),
			$result
		);

		// Assert we checkedout.
		if ( count( $result ) === 0 ) {
			$this->abort( 'Failed to checkout branch ' . $branch );
		}
	}

	/**
	 * Aborts the script.
	 *
	 * @param string $message
	 * @return void
	 */
	public static function abort( string $message ): void {
		self::output( $message );
		self::output( '!! SCRIPT ABORTED' );
		die();

	}

	/**
	 * Outputs a single line.
	 *
	 * @param string $message
	 * @param bool $new_line
	 * @return void
	 */
	public static function output( string $message, bool $new_line = true ): void {
		printf( '%s %s', $message, $new_line ? PHP_EOL : '' );
	}

	/**
	 * Prompts a question and awaits result.
	 *
	 * @param string $question
	 * @param string|null $default
	 * @return string
	 */
	public static function prompt( string $question, ?string $default = null ): string {
		self::output( $question );
		$handle = fopen( 'php://stdin', 'r' );
		$line   = fgets( $handle );
		fclose( $handle );

		if ( mb_strlen( $line ) <= 1 && $default === null ) {
			self::abort( 'YOU MUST ENTER A VALID RESPONSE!' );
		}
		return mb_strlen( $line ) <= 1 ? $default : trim( $line );
	}


}


// Autoload
$source      = Team51_Release::prompt( '** Please enter the source branch (trunk)', 'trunk' );
$destination = Team51_Release::prompt( '** Please enter the destination branch (release)', 'release' );

$builder = new Team51_Release( $source, $destination );
$builder->init();

// Mabybe push
$push = Team51_Release::prompt( '** Push to github for release [yes|y]' );
var_dump( strtolower( $push ) );
if ( in_array( strtolower( $push ), array( 'yes', 'y' ) ) ) {
	$new_tag = Team51_Release::prompt( '** Please enter the release version tag' );
	$builder->tag_release( $new_tag );
}
var_dump(
	array(
		$source,
		$destination,
	)
);



