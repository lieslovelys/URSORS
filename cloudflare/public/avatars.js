export const AVATARS = [
  { id: 'default', label: 'Classic', src: '/assets/images/default_profile.svg' },
  { id: 'blue', label: 'Blue', src: '/assets/images/avatars/blue.svg' },
  { id: 'teal', label: 'Teal', src: '/assets/images/avatars/teal.svg' },
  { id: 'purple', label: 'Purple', src: '/assets/images/avatars/purple.svg' },
  { id: 'rose', label: 'Rose', src: '/assets/images/avatars/rose.svg' },
  { id: 'amber', label: 'Amber', src: '/assets/images/avatars/amber.svg' },
  { id: 'slate', label: 'Slate', src: '/assets/images/avatars/slate.svg' }
];
export const avatarSrc = id => (AVATARS.find(avatar => avatar.id === id) || AVATARS[0]).src;
