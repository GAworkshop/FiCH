
module.exports = function( grunt ) {
	var paths = {
		js_files_concat: {
			'js/select2.3.js':        ['js/vendor/select2/select2.js'],
			'js/wpmu-ui.3.js':        [
				'js/src/wpmu-ui.js',
				'js/src/wpmu-ui-window.js',
				'js/src/wpmu-ui-progress.js',
				'js/src/wpmu-ui-hooks.js',
				'js/src/wpmu-ajaxdata.js',
				'js/src/wpmu-binary.js'
			],
			'js/wpmu-vnav.3.js':      ['js/src/wpmu-vnav.js'],
			'js/wpmu-card-list.3.js': ['js/src/wpmu-card-list.js']
		},

		css_files_compile: {
			'css/wpmu-ui.3.css':          'css/sass/wpmu-ui.scss',
			'css/wpmu-vnav.3.css':        'css/sass/wpmu-vnav.scss',
			'css/wpmu-card-list.3.css':   'css/sass/wpmu-card-list.scss',
			'css/wpmu-html.3.css':        'css/sass/wpmu-html.scss',
			'css/select2.3.css':          'css/sass/select2/select2.scss',
			'css/fontawesome.3.css':      'css/sass/font-awesome/font-awesome.scss',
			'css/animate.3.css':          'css/sass/animate-css/animate.scss',
			'css/jquery-ui.wpmui.3.css':  'css/sass/jquery-ui/jquery-ui-1.11.4.custom.scss'
		},

		plugin_dir: 'wpmu-lib/'
	};

	// Project configuration
	grunt.initConfig( {
		pkg:    grunt.file.readJSON( 'package.json' ),

		concat: {
			options: {
				stripBanners: true,
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			scripts: {
				files: paths.js_files_concat
			}
		},


		jshint: {
			all: [
				'Gruntfile.js',
				'js/src/**/*.js',
				'js/test/**/*.js'
			],
			options: {
				curly:   true,
				eqeqeq:  true,
				immed:   true,
				latedef: true,
				newcap:  true,
				noarg:   true,
				sub:     true,
				undef:   true,
				boss:    true,
				eqnull:  true,
				globals: {
					exports: true,
					module:  false
				}
			}
		},

		uglify: {
			all: {
				files: [{
					expand: true,
					src: ['*.js', '!*.min.js'],
					cwd: 'js/',
					dest: 'js/',
					ext: '.min.js',
					extDot: 'last'
				}],
				options: {
					banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
						' * <%= pkg.homepage %>\n' +
						' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
						' * Licensed GPLv2+' +
						' */\n',
					mangle: {
						except: ['jQuery']
					}
				}
			}
		},

		test:   {
			files: ['js/test/**/*.js']
		},


		phpunit: {
			classes: {
				dir: ''
			},
			options: {
				bin: 'phpunit',
				bootstrap: 'tests/php/bootstrap.php',
				testsuite: 'default',
				configuration: 'tests/php/phpunit.xml',
				colors: true,
				tap: true,
				//testdox: true,
				staticBackup: false,
				noGlobalsBackup: false
			}
		},


		sass:   {
			all: {
				options: {
					'sourcemap=none': true, // 'sourcemap': 'none' does not work...
					unixNewlines: true,
					style: 'expanded'
				},
				files: paths.css_files_compile
			}
		},


		cssmin: {
			options: {
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			minify: {
				expand: true,

				cwd: 'css/',
				src: ['*.css', '!*.min.css'],

				dest: 'css/',
				ext: '.min.css',
				extDot: 'last'
			}
		},


		watch:  {
			sass: {
				files: ['css/sass/**/*.scss'],
				tasks: ['sass', 'cssmin'],
				options: {
					debounceDelay: 500
				}
			},

			scripts: {
				files: ['js/src/**/*.js', 'js/vendor/**/*.js'],
				tasks: ['jshint', 'concat', 'uglify'],
				options: {
					debounceDelay: 500
				}
			}
		},


		clean: {
			main: {
				src: ['release/<%= pkg.version %>']
			},
			temp: {
				src: ['**/*.tmp', '**/.afpDeleted*', '**/.DS_Store'],
				dot: true,
				filter: 'isFile'
			}
		},


		copy: {
			// Copy the plugin to a versioned release directory
			main: {
				// Simple list for this plugin only (it has no submodules!)
				src:  [
					'**',
					'!.git/**',
					'!.git*',
					'!node_modules/**',
					'!release/**',
					'!.sass-cache/**',
					'!package.json',
					'!/css/sass/**',
					'!/js/src/**',
					'!/js/vendor/**',
					'!/img/src/**',
					'!/tests/**',
					'!/Gruntfile.js'
				],
				dest: 'release/<%= pkg.version %>/'
			}
		},


		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>/',
				src: [ '**/*' ],
				dest: paths.plugin_dir
			}
		}

	} );

	// Load other tasks
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');

	grunt.loadNpmTasks('grunt-contrib-sass');

	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-phpunit');

	grunt.registerTask( 'notes', 'Show release notes', function() {
		grunt.log.subhead( 'Release notes' );
		grunt.log.writeln( '  1. Check BITBUCKET for pull-requests' );
		grunt.log.writeln( '  2. Check ASANA for high-priority bugs' );
		grunt.log.writeln( '  3. Check EMAILS for high-priority bugs' );
		grunt.log.writeln( '  4. Check FORUM for open threads' );
		grunt.log.writeln( '  5. REPLY to forum threads + unsubscribe' );
		grunt.log.writeln( '  6. Update the TRANSLATION files' );
		grunt.log.writeln( '  7. Generate ARCHIVE' );
		grunt.log.writeln( '  8. INSTALL on a clean WordPress installation' );
		grunt.log.writeln( '  9. RELEASE the plugin!' );
	});

	// Default task.

	grunt.registerTask( 'default', ['clean:temp', 'jshint', 'concat', 'uglify', 'sass', 'cssmin', 'notes'] );
	grunt.registerTask( 'build', ['phpunit', 'default', 'clean', 'copy', 'compress', 'notes'] );
	grunt.registerTask( 'test', ['phpunit', 'jshint', 'notes'] );

	grunt.util.linefeed = '\n';
};