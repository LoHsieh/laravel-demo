/**
 * 以新台幣顯示金額，例如 NT$1,080。
 */
export const formatPrice = (price) => `NT$${price.toLocaleString('en-US')}`;
