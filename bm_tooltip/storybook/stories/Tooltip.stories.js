export default {
  title: "BlueMarloc/Tooltip",
};

const Template = ({ text, label, theme, position }) => `
  <span class="tooltip tooltip--${position} tooltip--${theme} tooltip--edge-aware"
        tabindex="0"
        data-tip="${text}">
    ${label}
  </span>
`;

export const Default = Template.bind({});
Default.args = {
  text: "Default tooltip",
  label: "Hover me",
  theme: "dark",
  position: "top"
};

export const Light = Template.bind({});
Light.args = {
  text: "Light tooltip",
  label: "Hover me",
  theme: "light",
  position: "top"
};

export const Accent = Template.bind({});
Accent.args = {
  text: "Accent tooltip",
  label: "Hover me",
  theme: "accent",
  position: "bottom"
};
