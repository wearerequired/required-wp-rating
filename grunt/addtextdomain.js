module.exports = {
  dist: {
    options: {
      textdomain   : 'required-wp-rating',
      updateDomains: []
    },
    target : {
      files: {
        src: ['*.php', '**/*.php', '!node_modules/**', '!tests/**']
      }
    }
  }
};