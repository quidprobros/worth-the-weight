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
            webIndex: { template: 'src/resources.html',
                        distFileName: 'resources.phtml',
                        publicPath: "/dist"
                      },
            devServer: false,
            dependencies: {
                // not needed as using modified version
                include: [
                    //'simple-jscalendar/source/jsCalendar.js'
                ]
            },
            cache: false,
            hmr: true,
            plugins: [
                pluginTypeChecker({
                    name: 'Superman',
                    tsConfig: './tsconfig.json',
                }),
                pluginSass(),
//                pluginCSS(),
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
    rm("./public/dist")

    await ctx.getConfig().runDev({
        bundles: {
            distRoot: 'public/dist',
            exported: true,
        },
    })
        .then(function() {
            src("./public/dist/resources.phtml")
                .dest("./views/generated", "dist")
                .exec()
        })
})

task("build", async (ctx: Context) => {
    rm("./public/dist")

    await ctx.getConfig().runProd({

        bundles: {
            distRoot: 'public/dist',
            exported: true,
        },
        manifest: false,
    }).then(() => {
        src("./public/dist/resources.phtml")
            .dest("views/generated", "dist")
            .exec()
    })
})

task("watch", () => {
    typeChecker.printSettings();
    typeChecker.inspectAndPrint();

    typeChecker.worker_watch('./');
})

