jQuery(document).on("click", ".pmw-notification-dismiss-button, .incompatible-plugin-error-dismissal-button", e => {

	// console.log("clicked")
	// console.log("jQuery(e.target).attr(\"data-plugin-slug\")", jQuery(e.target).attr("data-notification-id"))

	sendPmwNotificationDetails({
		element: jQuery(e.target),
		type   : "generic-notification",
	})
})

const sendPmwNotificationDetails = input => {

	fetch(pmwNotificationsApi.root + "pmw/v1/notifications/", {
		method : "POST",
		cache  : "no-cache",
		headers: {
			"Content-Type": "application/json",
			"X-WP-Nonce"  : pmwNotificationsApi.nonce,
		},
		body   : JSON.stringify({
			// notification: jQuery(e.target).attr("id"),
			type: input.type,
			id  : input.element.attr("data-notification-id"),
		}),
	})
		.then(response => {
			if (response.ok) {
				return response.json()
			}
		})
		.then(data => {
			if (data.success) {
				input.element.closest(".notice").fadeOut(300, () => {
					input.element.remove()
				})
			}
		})
}

jQuery(document).on("click", ".pmw.opportunity-dismiss", (e) => {

	const opportunityId = jQuery(e.target).attr("data-opportunity-id")
	const htmlElement   = jQuery(e.target)

	fetch(pmwNotificationsApi.root + "pmw/v1/notifications/", {
		method : "POST",
		cache  : "no-cache",
		headers: {
			"Content-Type": "application/json",
			"X-WP-Nonce"  : pmwNotificationsApi.nonce,
		},
		body   : JSON.stringify({
			notification : "dismiss_opportunity",
			opportunityId: opportunityId,
		}),
	})
		.then(response => {
			if (response.ok) {
				return response.json()
			}
		})
		.then(data => {
			if (data.success) {
				htmlElement.appendTo(".pmw-opportunity-dismissed")
			}
		})
})
