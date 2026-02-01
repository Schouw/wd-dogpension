    import { registerBlockType } from '@wordpress/blocks';
    import { useBlockProps } from '@wordpress/block-editor';
    import '../style.css';

    registerBlockType('wd-dogpension/book-form', {
        edit: () => {
            return <div id="mhhc_hundepension_form">EDIT</div>;
        },
        save: () => {
            return <div id="mhhc_hundepension_form">FRONTEND</div>;
        }
    });

