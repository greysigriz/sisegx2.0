// /src/plugins/motion-plugin.js
import { MotionPlugin } from '@vueuse/motion';

export default {
  install(app) {
    app.use(MotionPlugin, {
      directives: {
        'motion-slide-visible-once-bottom': {
          initial: {
            y: 100,
            opacity: 0,
          },
          visible: {
            y: 0,
            opacity: 1,
            transition: {
              duration: 800,
              ease: 'easeOut',
            },
          },
        },
        'motion-slide-visible-once-right': {
          initial: {
            x: 100,
            opacity: 0,
          },
          visible: {
            x: 0,
            opacity: 1,
            transition: {
              duration: 800,
              delay: 200,
              ease: 'easeOut',
            },
          },
        },
      },
    });
  },
};