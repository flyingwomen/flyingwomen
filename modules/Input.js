class FormKeyFilter {
	handleEvent(evt) {
		let elem = evt.srcElement;
		let key = evt.key;

		// Allow navigation keys to pass
		if (key == "ArrowLeft" || key == "ArrowRight" || key == "Tab" ||
			key == "ArrowUp" || key == "ArrowDown" || key == "Backspace" ||
			key == "End" || key == "PageUp" || key == "PageDown" || key == "Home")      return;

		// If a modifier key is held down then don't perform the check
		if (!evt.ctrlKey && !evt.metaKey && !evt.altKey) {
			if (elem.hasAttribute("data-allow")) {
				let allowregex = elem.getAttribute("data-allow")
				let testexpr = new RegExp(allowregex, "g");

				if (testexpr.test(key))		return;

				evt.preventDefault();
			}

			if (elem.hasAttribute("data-block")) {
				let blockregex = elem.getAttribute("data-block")
				let testexpr = new RegExp(blockregex, "g");
				if (testexpr.test(key))		 evt.preventDefault();
			}
		}
	}
 }