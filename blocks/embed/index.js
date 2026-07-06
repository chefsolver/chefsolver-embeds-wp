/**
 * ChefSolver Embed — Gutenberg block (buildless, WordPress core globals only).
 *
 * Server-side rendered: the editor preview and the front end both come from the
 * same PHP render_callback, so nothing about the iframe is duplicated in JS.
 * No remote JavaScript is loaded — manifest search hits the plugin's own REST
 * route on this site.
 *
 * @package ChefSolver_Embeds
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var cmp = wp.components;
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender;

	var DATA = window.ChefSolverEmbedsData || { langs: [ 'en' ], themes: [ 'auto', 'light', 'dark' ], types: [ 'converter' ], defaults: {} };

	function toOptions( values ) {
		return ( values || [] ).map( function ( v ) {
			return { label: v, value: v };
		} );
	}

	function edit( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var blockProps = useBlockProps ? useBlockProps() : {};

		var stateSearch = useState( '' );
		var search = stateSearch[ 0 ];
		var setSearch = stateSearch[ 1 ];

		var stateResults = useState( [] );
		var results = stateResults[ 0 ];
		var setResults = stateResults[ 1 ];

		var stateAvailable = useState( true );
		var available = stateAvailable[ 0 ];
		var setAvailable = stateAvailable[ 1 ];

		// Load manifest availability once.
		useEffect( function () {
			if ( ! apiFetch ) {
				return;
			}
			apiFetch( { path: 'chefsolver-embeds/v1/meta' } )
				.then( function ( meta ) {
					setAvailable( !! ( meta && meta.available ) );
				} )
				.catch( function () {
					setAvailable( false );
				} );
		}, [] );

		// Search entries when the query / filters change.
		useEffect( function () {
			if ( ! apiFetch ) {
				return;
			}
			var query = [
				'search=' + encodeURIComponent( search ),
				'type=' + encodeURIComponent( attributes.type || '' ),
				'lang=' + encodeURIComponent( attributes.lang || DATA.defaults.lang || '' ),
				'per_page=50',
			].join( '&' );
			apiFetch( { path: 'chefsolver-embeds/v1/entries?' + query } )
				.then( function ( items ) {
					setResults( Array.isArray( items ) ? items : [] );
				} )
				.catch( function () {
					setResults( [] );
				} );
		}, [ search, attributes.type, attributes.lang ] );

		var resultOptions = [ { label: __( '— Select a tool —', 'chefsolver-embeds' ), value: '' } ].concat(
			results.map( function ( r ) {
				return { label: r.title + '  [' + r.slug + ']', value: r.slug };
			} )
		);

		var controls = el(
			InspectorControls,
			{},
			el(
				cmp.PanelBody,
				{ title: __( 'ChefSolver embed', 'chefsolver-embeds' ), initialOpen: true },
				! available
					? el(
							cmp.Notice,
							{ status: 'warning', isDismissible: false },
							__( 'Manifest unavailable. Enter type, slug and language manually — rendering stays validated.', 'chefsolver-embeds' )
					  )
					: null,
				el( cmp.SelectControl, {
					label: __( 'Type', 'chefsolver-embeds' ),
					value: attributes.type || 'converter',
					options: toOptions( DATA.types && DATA.types.length ? DATA.types : [ 'converter' ] ),
					onChange: function ( v ) {
						setAttributes( { type: v } );
					},
				} ),
				el( cmp.SelectControl, {
					label: __( 'Language', 'chefsolver-embeds' ),
					value: attributes.lang || DATA.defaults.lang || 'en',
					options: toOptions( DATA.langs ),
					onChange: function ( v ) {
						setAttributes( { lang: v } );
					},
				} ),
				el( cmp.TextControl, {
					label: __( 'Search tools', 'chefsolver-embeds' ),
					value: search,
					onChange: setSearch,
					placeholder: __( 'e.g. grams, honey…', 'chefsolver-embeds' ),
				} ),
				el( cmp.SelectControl, {
					label: __( 'Available tools', 'chefsolver-embeds' ),
					value: attributes.slug || '',
					options: resultOptions,
					onChange: function ( v ) {
						var chosen = results.filter( function ( r ) {
							return r.slug === v;
						} )[ 0 ];
						var patch = { slug: v };
						if ( chosen ) {
							patch.type = chosen.type;
							patch.lang = chosen.lang;
							if ( ! attributes.height ) {
								patch.height = chosen.height;
							}
						}
						setAttributes( patch );
					},
				} ),
				el( cmp.TextControl, {
					label: __( 'Slug (manual override / fallback)', 'chefsolver-embeds' ),
					value: attributes.slug || '',
					onChange: function ( v ) {
						setAttributes( { slug: v } );
					},
					help: __( 'For ingredient converters use ingredient/conversion, e.g. honey/ml-to-grams.', 'chefsolver-embeds' ),
				} ),
				el( cmp.SelectControl, {
					label: __( 'Theme', 'chefsolver-embeds' ),
					value: attributes.theme || DATA.defaults.theme || 'auto',
					options: toOptions( DATA.themes ),
					onChange: function ( v ) {
						setAttributes( { theme: v } );
					},
				} ),
				el( cmp.TextControl, {
					label: __( 'Accent color (hex)', 'chefsolver-embeds' ),
					value: attributes.accent || '',
					onChange: function ( v ) {
						setAttributes( { accent: v } );
					},
					placeholder: '#b45309',
				} ),
				el( cmp.ToggleControl, {
					label: __( 'Compact', 'chefsolver-embeds' ),
					checked: !! attributes.compact,
					onChange: function ( v ) {
						setAttributes( { compact: !! v } );
					},
				} ),
				el( cmp.RangeControl, {
					label: __( 'Corner radius (px)', 'chefsolver-embeds' ),
					value: attributes.radius ? parseInt( attributes.radius, 10 ) : 0,
					min: 0,
					max: 40,
					onChange: function ( v ) {
						setAttributes( { radius: v ? String( v ) : '' } );
					},
				} ),
				el( cmp.RangeControl, {
					label: __( 'Height (px, 0 = recommended)', 'chefsolver-embeds' ),
					value: attributes.height || 0,
					min: 0,
					max: 2000,
					step: 10,
					onChange: function ( v ) {
						setAttributes( { height: v || 0 } );
					},
				} )
			)
		);

		var preview;
		if ( attributes.slug && ServerSideRender ) {
			preview = el( ServerSideRender, {
				block: 'chefsolver/embed',
				attributes: attributes,
			} );
		} else {
			preview = el(
				cmp.Placeholder,
				{
					icon: 'chart-bar',
					label: __( 'ChefSolver Embed', 'chefsolver-embeds' ),
					instructions: __( 'Pick a tool from the block settings (or enter a slug) to preview it.', 'chefsolver-embeds' ),
				}
			);
		}

		return el( Fragment, {}, controls, el( 'div', blockProps, preview ) );
	}

	wp.blocks.registerBlockType( 'chefsolver/embed', {
		edit: edit,
		save: function () {
			return null; // Dynamic (server-rendered).
		},
	} );
} )( window.wp );
