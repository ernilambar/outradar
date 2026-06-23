export default {
	build: {
		minify: true,
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
