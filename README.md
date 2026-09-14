# Contao Folder Gallery Download Extension Bundle

[![](https://img.shields.io/packagist/v/cgoit/contao-folder-gallery-download-extension-bundle.svg)](https://packagist.org/packages/cgoit/contao-folder-gallery-download-extension-bundle)
![Dynamic JSON Badge](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FcgoIT%2Fcontao-folder-gallery-download-extension-bundle%2Fmain%2Fcomposer.json\&query=%24.require%5B%22contao%2Fcore-bundle%22%5D\&label=Contao%20Version)
[![](https://img.shields.io/packagist/dt/cgoit/contao-folder-gallery-download-extension-bundle.svg)](https://packagist.org/packages/cgoit/contao-folder-gallery-download-extension-bundle)
[![CI](https://github.com/cgoIT/contao-folder-gallery-download-extension-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/cgoIT/contao-folder-gallery-download-extension-bundle/actions/workflows/ci.yml)

## Kurzüberblick

Das **Contao Folder Gallery Download Extension Bundle** erweitert das
[Contao Folder Gallery Bundle](https://github.com/cgoIT/contao-folder-gallery-bundle)
um eine Download-Funktion für einzelne Galerien.

Innerhalb einer Galerieansicht wird eine zusätzliche Action angezeigt, über die
sämtliche Bilder der aktuellen Galerie – einschließlich ihrer Unterordner – als
ZIP-Datei heruntergeladen werden können.

Die ZIP-Datei wird serverseitig erzeugt und anschließend als Datei zum Download
bereitgestellt. Die Bilder werden dabei nicht in den PHP-Speicher geladen.

> **Voraussetzung**
>
> Dieses Bundle erweitert das [Contao Folder Gallery Bundle](https://github.com/cgoIT/contao-folder-gallery-bundle)
> und kann nicht unabhängig davon verwendet werden.
>
> Zusätzlich wird die PHP-Erweiterung `zip` benötigt.

## Installation

Das Bundle kann wie jede andere Contao-Erweiterung über den **Contao Manager**
oder mit **Composer** installiert werden.

### Installation mit Composer

```bash
composer require cgoit/contao-folder-gallery-download-extension-bundle
```

### Installation mit dem Contao Manager

Das Bundle kann alternativ über den Contao Manager gesucht und installiert werden.

Nach der Installation ist keine zusätzliche Datenbankmigration erforderlich.

## Verwendung

Nach der Installation steht in einer Galerieansicht automatisch eine zusätzliche
Download-Action zur Verfügung.

Die Action wird innerhalb der **Galerieansicht** eines Ordners angezeigt, nicht in
der Galerie-Übersicht. Sie bezieht sich immer auf die aktuell dargestellte Galerie
einschließlich aller Unterordner.

Enthält die Galerie keine herunterladbaren Bilder – etwa weil sie leer ist, nur
unveröffentlichte Unterordner enthält oder alle Bilder ausgeblendet sind –, wird
keine Download-Action angezeigt. Wird der Download-Endpunkt für eine solche
Galerie dennoch direkt aufgerufen, antwortet er mit dem HTTP-Status `404`.

Beispielsweise kann eine Galerie

```text
2025/
├── Freitag/
│   ├── IMG_0001.jpg
│   ├── IMG_0002.jpg
│   └── ...
├── Samstag/
│   └── ...
└── Sonntag/
    └── ...
```

über die Galerieansicht von `Freitag` als ZIP-Datei heruntergeladen werden. Über die
Galerieansicht von `2025` enthält der Download dagegen die Bilder aller drei Tage.

Als Dateiname des Downloads wird der in den Metadaten gepflegte Titel der Galerie
verwendet. Ist kein Titel gepflegt, ergibt sich der Name aus dem letzten Teil des
Galeriepfads in der URL, beispielsweise:

```text
freitag.zip
```

Zeichen, die in Dateinamen auf gängigen Betriebssystemen nicht zulässig sind, werden
dabei ersetzt.

## Download

Beim Aufruf der Download-Action werden die Bilder der aktuellen Galerie und ihrer
Unterordner serverseitig zu einer ZIP-Datei zusammengefasst.

Dabei gilt:

- Die Originaldateien werden direkt aus dem Contao-Dateisystem gelesen.
- Die Bilddateien werden nicht in den PHP-Speicher geladen.
- Das ZIP-Archiv wird als temporäre Datei auf dem Server erzeugt.
- Anschließend wird das erzeugte Archiv als Datei an den Browser ausgeliefert und
  danach wieder gelöscht.

### Enthaltene Bilder

Der Download enthält dieselben Bilder, die auch im Frontend zu sehen sind:

- Bilder der aktuellen Galerie sowie – rekursiv – aller Unterordner.
- Unveröffentlichte Ordner (siehe **Veröffentlicht ab** / **Veröffentlicht bis** in
  den Metadaten des Folder Gallery Bundles) werden samt ihrer Unterordner
  übersprungen.
- Bilder, für die in der Contao-Dateiverwaltung die Option
  **In Ordner-Galerie verbergen** aktiviert ist, sind nicht enthalten (siehe
  [Einzelne Bilder aus der Galerie ausblenden](https://github.com/cgoIT/contao-folder-gallery-bundle#einzelne-bilder-aus-der-galerie-ausblenden)).
- Ist für einen Ordner **Titelbild in Galerie verbergen** aktiviert, ist das
  Titelbild dieses Ordners nicht enthalten.

### Aufbau des ZIP-Archivs

Die Bilder der aktuellen Galerie liegen auf oberster Ebene des Archivs. Bilder aus
Unterordnern werden in entsprechenden Ordnern abgelegt, die die Ordnerstruktur
unterhalb der Galerie widerspiegeln. Für die Ordner werden die Verzeichnisnamen im
Dateisystem verwendet, die Bilder behalten ihre ursprünglichen Dateinamen.

Der Download der Galerie `2025` aus dem obigen Beispiel ergibt damit:

```text
2025.zip
├── Freitag/
│   ├── IMG_0001.jpg
│   ├── IMG_0002.jpg
│   └── ...
├── Samstag/
│   └── ...
└── Sonntag/
    └── ...
```

Der Pfad der Galerie auf dem Server (z. B. `files/galerie/2025`) wird nicht in das
Archiv übernommen.

### Keine zusätzliche Bildkomprimierung

Bilder wie JPEG, WebP oder AVIF sind bereits komprimierte Dateiformate.

Das Bundle verzichtet deshalb bewusst auf eine zusätzliche ZIP-Komprimierung
der enthaltenen Bilder. Dadurch wird unnötige CPU-Zeit beim Erzeugen des Archivs
vermieden.

Das ZIP-Archiv dient damit hauptsächlich dazu, mehrere Dateien zu einem
einzigen Download zusammenzufassen.

## Speicherverbrauch

Beim Erzeugen eines Downloads werden die Bilddateien nicht vollständig in den
Arbeitsspeicher geladen.

Stattdessen wird ein temporäres ZIP-Archiv auf dem Dateisystem erzeugt:

```text
Contao-Dateisystem
       │
       │ Bilder
       ▼
GalleryZipCreator
       │
       │ temporäres ZIP
       ▼
temporäre Datei
       │
       ▼
Browser
```

Dadurch können auch größere Galerien verarbeitet werden, ohne dass die Größe
aller Bilder dem verfügbaren PHP-Speicher entsprechen muss.

Die benötigte temporäre Speicherkapazität entspricht dabei ungefähr der Größe
des erzeugten ZIP-Archivs.

> **Hinweis**
>
> Bei sehr großen Galerien kann die Erstellung und Übertragung des Downloads
> entsprechend lange dauern. Die tatsächlich erreichbare Geschwindigkeit hängt
> unter anderem vom verwendeten Webserver, PHP-Setup, Dateisystem und der
> Netzwerkverbindung ab.

## Anpassung

### Download-Action über ein Event deaktivieren

Die Download-Action wird standardmäßig für jede veröffentlichte Galerie mit
herunterladbaren Bildern angezeigt. Über das Symfony Event
`Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Event\GalleryDownloadActionEvent`
kann die Anzeige der Action individuell beeinflusst werden.

Das Event wird vor der Erstellung der Action ausgelöst und enthält den aktuellen
`GalleryOverview`, den `GalleryFolder` sowie das `PageModel`. Die Action ist
standardmäßig aktiviert und kann über `disable()` deaktiviert werden.

Für Galerien ohne herunterladbare Bilder wird das Event nicht ausgelöst, da die
Action in diesem Fall ohnehin nicht angezeigt wird.

Ein Event Listener kann die Action beispielsweise für bestimmte Ordner
ausblenden:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Event\GalleryDownloadActionEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class GalleryDownloadActionListener
{
    public function __invoke(GalleryDownloadActionEvent $event): void
    {
        if ('intern' === $event->folder->getPath()) {
            $event->disable();
        }
    }
}
```

Damit können beispielsweise abhängig von Ordnerpfad, Metadaten, Galerie oder
aktueller Seite eigene Regeln für die Anzeige der Download-Action umgesetzt
werden.

> **Hinweis**
>
> Das Event steuert ausschließlich die Anzeige der Action. Der Download-Endpunkt
> selbst wertet das Event nicht aus – eine über das Event ausgeblendete Galerie
> kann über die Download-URL weiterhin heruntergeladen werden.

### Visuelle Darstellung des Links im Frontend

Die Action besitzt eine eigene CSS-Klasse und kann daher über das eigene Theme angepasst werden.

Beispielsweise kann ein Download-Symbol über ein Pseudo-Element ergänzt werden:

```css
.gallery-content__action--download::before {
    content: "↓";
}
```

Die konkrete Darstellung der Actions kann außerdem über die entsprechenden Twig-Templates des Folder Gallery Bundles
angepasst werden.

## Technischer Aufbau

Das Bundle stellt eine Gallery-Action bereit, die über das Action-System des
Contao Folder Gallery Bundles automatisch erkannt wird.

Die Action erzeugt für die aktuelle Galerie einen Link zu einem eigenen
Download-Endpunkt (`/_folder-gallery/download/{moduleId}/{path}`).

Vereinfacht ergibt sich folgender Ablauf:

```text
Galerieansicht
      │
      ▼
DownloadGalleryAction
      │
      ├── Bilder ermitteln (keine Bilder → keine Action)
      └── GalleryDownloadActionEvent auslösen
      │
      ▼
GalleryDownloadController
      │
      ├── GalleryZipImageCollector: Bilder rekursiv ermitteln
      │                             (keine Bilder → 404)
      ├── GalleryZipCreator: temporäres ZIP erzeugen
      └── GalleryDownloadFilenameGenerator: Dateiname bestimmen
      │
      ▼
BinaryFileResponse
      │
      ▼
Browser
```

Das Bundle verwendet dabei die vorhandenen `GalleryImage`-Objekte des
Folder Gallery Bundles und greift direkt auf die zugehörigen Dateien im
Contao-Dateisystem zu.

## Mitwirken

Fehlerberichte, Verbesserungsvorschläge und Pull Requests über GitHub sind
jederzeit willkommen.

Falls Sie Fragen oder Ideen zur Erweiterung haben, freuen wir uns über ein
Issue oder eine Diskussion auf GitHub.

## Lizenz

Dieses Bundle steht unter der **LGPL-3.0-or-later**.

Weitere Informationen finden Sie in der Datei `LICENSE`.
