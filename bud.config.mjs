// @ts-check

/**
 * Build configuration for bud.js
 * @param bud {import('@roots/bud').Bud}
 */
 export default async bud => {
	/**
	 * The bud instance
	 */
	bud
	  /**
	   * Set the project source directory
	   */
	  .setPath('@src', 'assets')
  
	  /**
	   * Set the application entrypoints
	   * These paths are expressed relative to the '@src' directory
	   */
	  .entry({
		app: ['js/app.js'],
	  })

	  .assets({
		from: bud.path('@src/static/'),
		to: bud.path('@dist/static'),
	  })

	  .hash()

	  .minimize()
  }
