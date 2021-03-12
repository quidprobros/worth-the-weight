declare module "htmx.org"

interface IHtmxConfig {
    historyEnabled: boolean,
    historyCacheSize: number,
    refreshOnHistoryMiss: boolean,
    defaultSwapStyle: 'innerHTML',
    defaultSwapDelay: number,
    defaultSettleDelay: number,
    includeIndicatorStyles: boolean,
    indicatorClass:'htmx-indicator',
    requestClass:'htmx-request',
    settlingClass:'htmx-settling'|string,
    swappingClass:'htmx-swapping'|string,
    allowEval:true,
    attributesToSettle:["class", "style", "width", "height"]
}

interface IHtmx {
    onLoad(callback: () => void): void
    on(): void
    off(): void

    config: IHtmxConfig
}

declare global {
    interface Window {
        htmx: IHtmx
    }
}

export {}

