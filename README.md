# UX Select

WordPress-plugin for Flatsome UX Builder. Opprett valglister i admin, plasser en **Select** hvor som helst, og vis innhold i **Select-innhold**-paneler som kan ligge et annet sted på siden.

**Versjon:** 1.0.0  
**Forfatter:** Benjamin Pedersen  
**Dokumentasjon:** [kodekassa.no/plugins/ux-select](https://kodekassa.no/plugins/ux-select)

## Krav

- WordPress
- Flatsome-tema (for UX Builder-elementene; shortcodene rendres uten)

## Installasjon

1. Last ned release-zip fra [Releases](https://github.com/bli-synlig-plugins/ux-select/releases), eller klon dette repoet.
2. Legg mappen `ux-select` i `wp-content/plugins/`.
3. Aktiver **UX Select** under **Plugins**.

## Innstillinger

**Innstillinger → UX Select**

Lag **sett** (f.eks. Fylker) med **valg** (Østfold, Akershus, …). Slug genereres fra navnet ved første lagring og endres ikke etterpå.

## UX Builder

Begge elementene ligger under **Content**.

1. **Select** — velg settet. Valgfri ledetekst, placeholder og stil.
2. **Select-innhold** — container. Velg sett og kryss av ett eller flere valg (f.eks. Østfold og Akershus). Dropp tekst, rader og andre UX-elementer inni.

Samme panel kan vises for flere valg. Flere paneler kan bruke samme valg. Flere Select-felt på samme sett følger hverandre. I builder vises alle paneler med en merkelapp, slik at de kan redigeres.

## Shortcodes

```
[uxs_select set="fylker" label="Velg fylke"]
[uxs_panel set="fylker" v_fylker_ostfold="true" v_fylker_akershus="true"]Innhold for Østfold og Akershus[/uxs_panel]
```

Eldre `target="fylker:ostfold"` virker fortsatt.

## Lisens

Ingen lisens er angitt i repoet ennå.
