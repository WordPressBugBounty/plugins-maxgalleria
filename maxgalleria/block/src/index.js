import { registerBlockType } from '@wordpress/blocks';
const { __ } = wp.i18n;

registerBlockType( 'maxgalleria/gallery', {
    title: __( 'MaxGalleria Gallery', 'maxgalleria' ),
    icon: 'format-gallery',
    category: 'embed',
    keywords: [
      'gallery',
      'maxgalleria'
    ],
    attributes: {
      galleryID: {
        type: 'string',
        selector: 'select',
        attribute: 'value',
        default: ''
      }
    },  


    edit: function({ attributes, setAttributes, className }) {
    function onChangeContent( event ) {
      setAttributes( { galleryID: event.target.value } );
    } 

    function getGalleryList() {
      
      return mg_block_info.galleries.map((item, i) => {
          return (
            <option key={i} value={item.id}>{item.name}</option>
          )
      });    
  
    }  
    
    return (
      <div className={className}>
        <div>{ __( 'MaxGalleria Gallery', 'maxgalleria' ) }</div>
        {mg_block_info.galleries.length == 0
        ? <div>{ __( 'No galleries found', 'maxgalleria' ) }</div>
        : <select value={attributes.galleryID} onChange={onChangeContent}>
          <option value="" disabled >{ __( 'Select a gallery', 'maxgalleria' ) }</option>
          {getGalleryList()}
          </select>
      }
      </div>
    )

         
    },
    save: function({attributes, className}) {
      return (
        <div  className={className}>
          {'[maxgallery id="'+ attributes.galleryID + '"]'}
        </div>
      )
    }
  
} );
