<div <?php echo get_block_wrapper_attributes(); ?>>
<?php
$id = sanitize_title( $attributes['widgetArea'] );
/**
 * If the sidebar is empty, output the fallback content.
 */
if ( is_active_sidebar( $id ) ) {
    dynamic_sidebar( $id );
} else {
    echo $content;
}
?>
</div>
