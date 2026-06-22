export default {
	build: {
		minify: false,
		outDir: 'build',
		rollupOptions: {
			input: 'src/main.js',
			output: {
				entryFileNames: '[name].js',
				assetFileNames: '[name].[ext]',
			},
		},
	},
};
