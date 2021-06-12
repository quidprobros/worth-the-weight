export const App = {
    get resetForms(): HTMLFormElement[] {
        return $.each($("form"), (_i: number, el: HTMLFormElement) => {
            el.reset()
        }) as HTMLFormElement[]
    },
    set togglePassword(el: HTMLInputElement) {
        const viztype = el.type === 'password' ? 'text' : 'password'
        el.type = viztype
    },
    set eyeball(el: HTMLElement) {
        el.classList.toggle('gg-eye');
        el.classList.toggle('gg-eye-alt');
    },
    delayPromise(duration: number): Promise<number> {
        return new Promise((resolve): void => {
            setTimeout(() => {
                resolve(duration)
            }, duration)
        })
    }
}
