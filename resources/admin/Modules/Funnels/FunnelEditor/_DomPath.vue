<template>
    <div :style="css" :class="'fc_dom_path_' + side" class="fc_dom_path"><slot></slot></div>
</template>

<script type="text/babel">
export default {
    name: 'DomPath',
    props: ['from', 'to', 'label', 'side'],
    data() {
        return {
            css: {}
        }
    },
    methods: {
      generateCss() {
          const $parent = jQuery(this.$el).closest('.block_item_holder_conditional');
          const parent = $parent.offset();
          const from = $parent.find('.' + this.from).offset();
          const to = $parent.find('.' + this.to).offset();

          const height = to.top - from.top;
          let left = 0;
          if (this.side == 'left') {
              left = from.left - parent.left - 72;
          } else {
              left = from.left - parent.left;
          }

          const top = 50;

          const css = {
              position: 'absolute',
              border: '2px solid #2f2925',
              width: '81px',
              left: left + 'px',
              top: top + 'px',
              height: height + 'px',
              borderBottom: '0',
              padding: '8px 20px 0 0',
              fontWeight: '600',
              marginLeft: '0'
          }

          if (this.side == 'left') {
              css.borderRight = '0';
              css.borderTopLeftRadius = '20px';
          } else {
              css.borderLeft = '0';
              css.borderTopRightRadius = '20px';
              css.padding = '8px 0 0 20px';
              css.marginLeft = '-2px'
          }

          this.css = css;
      }
    },
    mounted() {
        this.generateCss();

        jQuery(window).on('resize', () => {
            this.generateCss();
        });
    }
}
</script>
