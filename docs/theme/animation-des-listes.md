
## 💡 Dernier petit conseil pour la mise en production

Pensez à bien tester le rendu final sur mobile (smartphone). Si vous remarquez que le décalage horizontal de 2rem (transform: translate(-2rem, 3rem)) fait déborder vos textes en dehors de l'écran ou crée un scroll horizontal indésirable, vous pouvez encapsuler vos @keyframes dans une simple règle responsive pour adoucir le mouvement sur petit écran :

## Le code à ajouter

@media (max-width: 768px) {
    @keyframes slowFloatAndSettle {
        0% {
            opacity: 0;
            transform: translate(-0.8rem, 2rem) rotate(4deg) scale(0.96); /* Amplitude réduite sur mobile */
        }
        /* Le reste des étapes (35%, 75%, 100%) reste identique */
    }
}
