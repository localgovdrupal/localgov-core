(function GinLayoutParagraphsScript(Drupal) {
  Drupal.behaviors.GinLayoutParagraphs = {
    attach(context) {
      function ginLayoutParagraphsFix() {
        const layoutParagraphsDialogs = context.querySelectorAll(
          '.ui-dialog.layout-paragraphs-dialog',
        );
        layoutParagraphsDialogs.forEach((layoutParagraphsDialog) => {
          const form = layoutParagraphsDialog.closest('form');
          const overlay = context.querySelector('.ui-widget-overlay.ui-front');
          const paragraphsField = layoutParagraphsDialog.closest(
            '.field--type-entity-reference-revisions',
          );
          const placeholder = document.createElement('div');
          placeholder.classList.add(
            'js-layout-paragraph-base-field-placeholder',
          );

          if (
            !layoutParagraphsDialog.classList.contains(
              'js-localgov-layout-paragraph-processed',
            )
          ) {
            if (!layoutParagraphsDialog.classList.contains('js-modal-moved')) {
              // Add a placeholder so the field can be moved back to here
              // when the modal is closed.
              paragraphsField.insertBefore(
                placeholder,
                paragraphsField.querySelector('.form-item'),
              );

              // Move the overlay and the paragraph entity reference field
              // to be placed just before we close </form>.
              form.insertBefore(
                overlay,
                context.querySelector('form > *:last-child').nextSibling,
              );
              form.insertBefore(
                paragraphsField,
                context.querySelector('form > *:last-child').nextSibling,
              );
              layoutParagraphsDialog.classList.add('js-modal-moved');

              // When modal is removed, move the paragraph entity reference field back.
              layoutParagraphsDialog.addEventListener('remove', () => {
                placeholder.replaceWith(paragraphsField);
              });
            }
            layoutParagraphsDialog.classList.add(
              'js-localgov-layout-paragraph-processed',
            );
          }
        });
      }

      ginLayoutParagraphsFix();
    },
  };
})(Drupal);
