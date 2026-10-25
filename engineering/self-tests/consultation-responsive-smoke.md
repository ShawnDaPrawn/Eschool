# Responsive consultation smoke navigation

The classic Teacher consultation smoke previously looked for an InkWell around
an inactive mobile tab label. Desktop Chrome renders a NavigationRail instead,
so that selector never found the Settings destination.

The test now selects the visible Settings rail destination on desktop, or the
actual BottomNavItemContainer on a phone. Product navigation is unchanged.
The real full-app headless Chrome flow passes using the CI default viewport
(no browser-dimension override), including sign-in, Notifications, the protected
consultation action, anonymous ballot submission, exact school/account headers
and foreground access-withdrawal erasure. Earlier phone-width flows remain
recorded in the consultation application evidence; this correction specifically
reproduced and qualified the default desktop layout.
