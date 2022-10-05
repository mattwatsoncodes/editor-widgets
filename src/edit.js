import { InnerBlocks, InspectorControls, useBlockProps, useInnerBlocksProps, store as blockEditorStore } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( props ) {
	const {
		attributes,
		clientId,
		setAttributes,
	} = props;

	const { widgetArea } = attributes;

	const blockProps = useBlockProps(); // eslint-disable-line react-hooks/rules-of-hooks

	const { hasInnerBlocks } = useSelect(
		( select ) => {
			const { getBlock } = select( blockEditorStore );
			const block = getBlock( clientId );
			return {
				hasInnerBlocks: !! ( block && block.innerBlocks.length ),
			};
		},
		[ clientId ]
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps,
		{
			renderAppender: hasInnerBlocks
				? undefined
				: InnerBlocks.ButtonBlockAppender,
		}
	);

	/**
	 * Alternative way to save details.
	 *
	 * Leaving this commented code here, as it provides a save method and a get method for the
	 * settings, that work on the FSE, which might be useful in the future.
	 */
	// useEffect( () => {
	// 	subscribe( () => {
	// 		const isSavingPost = select('core/editor').isSavingNonPostEntityChanges();

	// 		if ( ! isSavingPost ) {
	// 			return;
	// 		}

	// 		if ( widgetState === '' ) {
	// 			return;
	// 		}

	// 		let newWidgetAreas = [...new Set( widgetAreas )];
	// 		newWidgetAreas = newWidgetAreas.filter( value => value !== '' );
	// 		if ( ! newWidgetAreas.includes( widgetState ) ) {
	// 			newWidgetAreas.push( widgetState );
	// 		}

	// 		const settings = new api.models.Settings( {
	// 			[ 'editor-widgets' ]: newWidgetAreas,
	// 		} );
	// 		settings.save();

	// 	});

	// 	api.loadPromise.then( () => {
	// 		const settings = new api.models.Settings();

	// 		if ( isAPILoaded === false ) {
	// 			settings.fetch().then( ( response ) => {
	// 				console.log( response );
	// 				setWidgetAreas( response[ 'editor-widgets' ] || [] );
	// 				setIsAPILoaded( true );
	// 			} );
	// 		}
	// 	} );
	// });

	return (
		<div { ...blockProps }>
			<InspectorControls>
			<PanelBody
				className="widget-area-name"
				title="Widget Area Details"
			>
				<TextControl
					help="We would likely power this via a taxonomy."
					label="Widget Area Name"
					value={ widgetArea }
					onChange={ ( value ) => setAttributes( { 'widgetArea': value } ) }
				/>
			</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</div>
	);
}
