import { registerBlockType } from '@wordpress/blocks';
import metadata from '../../../blocks/booking-form/block.json';
import Edit from './edit';
import '../../styles/widget.scss';
import '../../styles/editor.scss';

registerBlockType( metadata, {
	edit: Edit,
	save: () => null, // Rendered on the server (includes/render/form.php).
} );
