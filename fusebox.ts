const path = require('path');

import { fusebox, sparky, pluginCSS, pluginSass, pluginPostCSS } from 'fuse-box';

import { pluginTypeChecker } from 'fuse-box-typechecker';
import { IPublicConfig } from 'fuse-box/config/IConfig';

const typeChecker = require('fuse-box-typechecker').TypeChecker({
    tsConfig: './tsconfig.json',
    basePath: './',
    name: 'checkerSync',
    print_summary: true,
});

class Context {
    public outputType!: string
    public config: IPublicConfig = {}

    public getConfig() {
        return fusebox({
            entry: "src/index.ts",
            stylesheet: {
                macros: {
                    '@': path.resolve(__dirname, './node_modules/'),
                },
            },
            logging: {
                level: 'verbose',
            },
            target: 'browser',
            webIndex: { template: 'src/resources.html',
                        distFileName: 'resources.phtml',
                        publicPath: "/dist"
                      },
            devServer: false,
            cache: false,
            hmr: true,
            plugins: [
                pluginTypeChecker({
                    name: 'Superman',
                    tsConfig: './tsconfig.json',
                    // throwOnGlobal: true,
                    // throwOnSemantic: true,
                    // throwOnSyntactic: true,
                }),
                pluginSass(),
                pluginPostCSS()
            ],
        })
    }
}

const {
    rm,
    src,
    task,
    exec,
} = sparky<Context>(Context)

task('clean', async () => {
    await rm("./wtw.paxperscientiam.com/dist/")
})

task("run-dev", async (ctx: Context) => {
    await ctx.getConfig().runDev({
        bundles: {
            distRoot: 'wtw.paxperscientiam.com/dist',
            exported: true,
            app: 'app.$hash.js',
            vendor: 'vendor.$hash.js',
            styles: 'styles/styles.$hash.css',
        },
    })

})

task("run-build", async (ctx: Context) => {
    await ctx.getConfig().runProd({
        bundles: {
            distRoot: 'wtw.paxperscientiam.com/dist',
            exported: true,
            app: 'app.$hash.js',
            vendor: 'vendor.$hash.js',
            styles: 'styles/styles.$hash.css',
        },
        manifest: false,
    })
})

task("default", async () => {
    await exec('clean')
    await exec('run-dev')
})

task("build", async () => {
    await exec('clean')
    await exec('run-build')
})

task("watch", () => {
    typeChecker.printSettings();
    typeChecker.inspectAndPrint();
    typeChecker.worker_watch('./');
})

