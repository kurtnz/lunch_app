module.exports = function(grunt) {

  // Project configuration.
    grunt.initConfig({
        lint: {
          all: [
            'grunt.js',
            'web/static/js/*.js',
            '!web/static/js/bootstrap.min.js'
          ]
        },
        jshint: {
          options: {
            curly: true,
            eqeqeq: true,
            immed: true,
            latedef: true,
            newcap: true,
            noarg: true,
            sub: true,
            undef: true,
            boss: true,
            eqnull: true,
            node: true,
            es5: true,
            strict: false
          },
          globals: {}
        },
        watch: {
            scripts: {
                files: 'web/static/js/*.js',
                tasks: ['jshint']
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-watch');

    // Default task.
    grunt.registerTask('default', 'lint');

};
