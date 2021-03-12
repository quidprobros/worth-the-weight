const path = require('path');

import { fusebox, sparky, pluginCSS, pluginSass } from 'fuse-box';

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
            webIndex: false,
            devServer: false,
            cache: false,
            hmr: true,
            plugins: [
                pluginTypeChecker({
                    name: 'Superman',
                    tsConfig: './tsconfig.json',
                }),
                pluginSass(),
                //pluginCSS(),
            ],
        })
    }
}


const {
    rm,
    task,
} = sparky<Context>(Context)

task("default", async (ctx: Context) => {
    await ctx.getConfig().runDev({

    })
        .then(function() {

        })
    // await fuse.runDev({
    //     bundles: {
    //         distRoot: "./dist/js",
    //         app: 'index.js',
    //     },
    // })
})

task("build", async (ctx: Context) => {
    rm("./dist")

    // ctx.extendConfig({
    //     webIndex: false,
    //     devServer: false,
    // })

    await ctx.getConfig().runProd({
        manifest: false,
        bundles: {
            app: 'index.js',
        },
    })
        .then(function() {
            console.log("Done building ESM module")
        })
})

task("watch", () => {
    typeChecker.printSettings();
    typeChecker.inspectAndPrint();

    typeChecker.worker_watch('./');
})
 
