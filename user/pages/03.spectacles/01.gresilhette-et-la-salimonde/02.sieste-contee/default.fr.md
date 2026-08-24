---
title: 'La sieste contée'
visible: false
routable: true
process:
  markdown: true
  twig: true
---
<div class="p_sieste-contee">
<a class="{{ page.slug != 'theatre-d-ombres' ? 'image-wrapper morph' : '' }}" href="{{ page.parent.url }}/theatre-d-ombres" title="Voir le théâtre d'ombres">
<img src="{{ page.parent.media['sp_theatre-d-ombres.png'].resize(200, 200).quality(85).url }}" alt="Le théâtre d'ombres - Grésilhette et la Salimonde">
</a>
<a class="{{ page.slug != 'sieste-contee' ? 'image-wrapper morph' : '' }}" href="{{ page.parent.url }}/sieste-contee" title="Voir la sieste contée">
<img src="{{ page.parent.media['sp_sieste-contee.png'].resize(200, 200).quality(85).url }}" alt="La sieste contée - Grésilhette et la Salimonde">
</a>
<a class="{{ page.slug != 'projet-pedagogique' ? 'image-wrapper morph' : '' }}" href="{{ page.parent.url }}/projet-pedagogique" title="Voir le projet pédagogique">
<img src="{{ page.parent.media['sp_projet-pedagogique.png'].resize(200, 200).quality(85).url }}" alt="Le projet pédagogique - Grésilhette et la Salimonde">
</a>
</div>
<p class="p_first">La sieste contée propose une expérience douce, sensible et immersive à
partir de l'univers de <em>Grésilhette et la Salimonde</em>. Cette forme privilégie
l'écoute, le rythme et l'imaginaire, dans une ambiance propice au relâchement et à la
rêverie.</p>
<p>Elle peut être programmée en bibliothèque, en école, en festival ou en structure associative
sur le territoire audois et en Occitanie et plus largement. Pour organiser une séance adaptée
à votre public, vous pouvez nous <a data-type="external" href="mailto:compagnie@lesmegeresdelhumus.fr?subject=Message du site, page Sieste Contée">écrire</a><span class="lien-e-seul"></span>.</p>
<picture class="sigean-sieste-contee">
<img src="{{ page.media['sigean-1200w.webp'].resize(800).url }}" alt="Fiche technique Sieste Contée de la compagnie Les Mégères de l'Humus" loading="lazy">
</picture>

<div class="g_art-tech">
<a target="_blank" rel="noopener noreferrer" href="{{ page.url }}/fiche-sieste-contee.pdf" title="Visualiser la fiche technique Sieste Contée">
<img  alt="" src="{{ page.media['btn-tech-sieste.webp'].resize(300).url }}" loading="lazy">
</a>
</div>