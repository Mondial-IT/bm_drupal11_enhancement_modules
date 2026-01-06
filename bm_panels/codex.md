Codex instructions for bm_panels module
rule: Only alter code in `modules/custom/bm_panels` module, no code outside this module may be altered.

The below-mentioned list contains instructions to execute.
Each feature has a number and a markdown checkbox * [ ]
The user instructs which feature (nr) to build next or to build all unchecked features.
When a feature is created (ready) you check the box (* [x]) in this document.

## Design notes:

Adding a panel (by the user interaction), will be handled via an ajax call (which can then respond with redisplay removed panels), removing a panel is also an ajax activity and redisplay of the removed-panels update. This way the build
form can always be aware of the number of displayed and removed panels and update both the removed panel selectord and the other panel elements trough ajax. A good architecture would be on any ajax call, to re-analyse which panels should be or become
visible together with control elements such as the 'removed panels' selector.
This creates an architecture where build form analyses which panels should be displayed and uses a general ajax response to update those.


## features

* [x] feature 1. Optimize bm_panels.css,

* [x] feature 2. Using bluemarloc colors,
locate color definitions like in color, background, border, shadow
and replace with a variable. Define all variables at the heading of the file.
for 'default mode' replace colors in the heading with variable `bm_bluemarloc_purple` and `bm_bluemarloc_purple_background`.
for 'prefer dark mode' replace colors in the heading with variable `bm_bluemarloc_blue` and `bm_bluemarloc_blue_background`.

* [x] feature 3. display `Removed panels:` as `select` element on top,
where `on selecting` the panel is 'restored'.

* [x] feature 4. revisit. Only display the element `bm-panel__toolbar` when:
A. there are panels displayed which have the property 'deletable'
B. AND, when a panel is deleted.

* [x] feature 5. Currently, demo info text is displayed like `Configured as draggable, pinned, size 11×6`
todo: only display this text on the demo pages.

* [x] feature 6. An ajax refresh or load, currently 'adds' bm-panel__toolbar below it.
That toolbar should stay at its position.  It should probably be refreshed independently
from the panels.
