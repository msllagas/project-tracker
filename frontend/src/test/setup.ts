// jsdom has no modal dialog support yet; emulate the parts the app relies on.
HTMLDialogElement.prototype.showModal = function (this: HTMLDialogElement) {
  this.open = true
}

HTMLDialogElement.prototype.close = function (this: HTMLDialogElement) {
  this.open = false
}
