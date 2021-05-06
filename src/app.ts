export const App = {
    get resetForms() {
        return $("form").each((i, el: HTMLFormElement) => {
            el.reset()
        })
    },

    get do() {
        return {
            get bitch() {
                return 33
            }
        }
    }
}
