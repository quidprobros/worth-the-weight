export const App = {
    get resetForms(): HTMLFormElement[] {
        return $.each($("form"), (_i: number, el: HTMLFormElement) => {
            el.reset()
        }) as HTMLFormElement[]
    },
}
