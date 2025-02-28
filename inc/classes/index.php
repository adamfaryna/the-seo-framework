<?php
/**
 * Class call tree
 *
 * # Namespace: The_SEO_Framework
 *
 * ## Separated:
 *    - Deprecated
 *       |-> Final
 *    - Debug
 *       |-> Interface:
 *          -  Debug_Interface
 *       |-> Final
 *
 * ## Failsafe:
 *    - Silencer
 *       |-> Final
 *
 * ## Façade (bottom is called first):
 *    -  | Core
 *       | Query
 *       | Init
 *       | Admin_Init
 *       | Render
 *       | Detect
 *       | Post_Data
 *       | Term_Data
 *       | User_Data
 *       | Generate
 *       | Generate_Description
 *       | Generate_Title
 *       | Generate_Url
 *       | Generate_Image
 *       | Generate_Ldjson
 *       | Profile
 *       | Edit
 *       | Admin_Pages
 *       | Sanitize
 *       | Site_Options
 *       | Metaboxes
 *       | Cache
 *       | Feed
 *       | Load
 *          |-> Interface:
 *             - Debug_Interface
 *          |-> Final
 *          |-> Instance
 */
