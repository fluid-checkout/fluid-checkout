/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';



/**
 * Internal dependencies
 */
import json from './block.json';
import icon from './icon.js';
import edit from './edit';
import save from './save';



// Destructure the json file to get the name of the block
// For more information on how this works, see: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Destructuring_assignment
const { name } = json;

// Register the block
registerBlockType( name, {
	icon,
	edit,
	save,
} );
