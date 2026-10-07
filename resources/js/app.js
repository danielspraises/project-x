import './bootstrap';

import Alpine from 'alpinejs';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

window.Alpine = Alpine;
window.TiptapEditor = Editor;
window.TiptapStarterKit = StarterKit;

Alpine.start();