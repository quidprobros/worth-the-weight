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
} = sparky<Context>(Context)

task("default", async (ctx: Context) => {
    rm("./wtw.paxperscientiam.com/dist")

    await ctx.getConfig().runDev({
        bundles: {
            distRoot: 'wtw.paxperscientiam.com/dist',
            exported: true,
            app: 'app.$hash.js',
            vendor: 'vendor.$hash.js',
            styles: 'styles/styles.$hash.css',
        },
    })
        .then(function() {
            // rm("./wtw.paxperscientiam.com/dist/resources.phtml")
            // return
            // src("./wtw.paxperscientiam.com/dist/resources.phtml")
            //     .dest("./views/generated", "dist")
            //     .exec()
        })
})

task("build", async (ctx: Context) => {
    rm("./wtw.paxperscientiam.com/dist")

    await ctx.getConfig().runProd({
        bundles: {
            distRoot: 'wtw.paxperscientiam.com/dist',
            exported: true,
            app: 'app.$hash.js',
            vendor: 'vendor.$hash.js',
            styles: 'styles/styles.$hash.css',
        },
        manifest: false,
    }).then(() => {
        src("./wtw.paxperscientiam.com/dist/resources.phtml")
            .dest("views/generated", "dist")
            .exec()
      //  rm("./wtw.paxperscientiam.com/dist/resources.phtml")
    })
})

task("watch", () => {
    typeChecker.printSettings();
    typeChecker.inspectAndPrint();

    typeChecker.worker_watch('./');
})

