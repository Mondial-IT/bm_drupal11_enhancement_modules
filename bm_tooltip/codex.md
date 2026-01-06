Codex instructions for bm_tooltip module.
rule: Only alter code in `modules/custom/bm_tooltip` module, no code outside this module may be altered.

The below-mentioned list contains instructions to execute.
Each feature has a number and a markdown checkbox * [ ]
rule: The user instructs which feature (nr) to build next or to build all unchecked features.
rule: When a feature is created (ready) you check the box (* [x]) in this document.


* [x] feature 1. Add help_topics to the module explaining
how to work with the bm_tooltip module.

* [x] feature 2. fix wrap/position issue for longer tooltip texts.
It should wrap when text makes the end fall off the screen.
It should wrap when text is longer than 10 words.

* [x] feature 2.1. issue, currently the width is too small to display a nice tooltip text.
* when there are 7 words or more the minimum width should be 7 words.
when there are less than 7 words, the minimum is the length of that content.

* [x] feature 3. fix a 'z-index' issue when there are panels next to it
then the tooltip is hidden under the elements next to it.

* [x] feature 4. Replace the current tooltip implementation with the Tippy.js library.
