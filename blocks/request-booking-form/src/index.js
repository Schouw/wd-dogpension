    import { registerBlockType } from '@wordpress/blocks';
    import { useBlockProps } from '@wordpress/block-editor';
    import '../style.css';

    registerBlockType('wd-dogpension/book-form', {
        edit: () => {
            return <div id="mhhc_hundepension_form">Dog Pension Form</div>;
        },
        save: () => {
            return <div id="mhhc_hundepension_form"></div>;
        }
    });

