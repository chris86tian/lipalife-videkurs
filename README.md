# Lipa LIFE Videokurs

Version: 2.7

## Neue Funktionen in 2.7

- **Verbesserter CSV Import/Export:** Der Export verwendet nun den Namen des Eltern-Terms ("Parent Name") anstelle der ID, um die Zuordnung von Lektionen zu Modulen/Kursen und Modulen zu Kursen beim Import über verschiedene WordPress-Installationen hinweg korrekt beizubehalten. Der Import wurde robuster gestaltet, um auch CSV-Dateien mit Anführungszeichen in den Headern und BOM-Zeichen korrekt zu verarbeiten.
- **Detaillierte Fehlermeldungen beim CSV Import:** Fehler während des Imports werden nun detaillierter angezeigt, um das Debuggen zu erleichtern.
- **Kurslöschfunktion mit Sicherheitsabfrage und Export:** Eine neue Admin-Seite ermöglicht das sichere Löschen einzelner Kurse. Vor dem endgültigen Löschen wird eine Sicherheitsabfrage angezeigt und die Option geboten, den Kurs vorher zu exportieren.
- **Lektionstextfarben angepasst:**
    - Standard-Textfarbe für Lektionen in der Navigation ist nun Schwarz.
    - Die Textfarbe der aktiven Lektion verwendet jetzt die Farbe des "Abschließen" Buttons.
    - Die Textfarbe für abgeschlossene Lektionen bleibt Grün.
- **Backend-Einstellungen für Button-Farben (erweitert):** Konfigurieren Sie nun auch die Farbe für den "Kurs starten" Button in der Kursübersicht über die Einstellungsseite im Backend.
- **Backend-Einstellungen für Button-Farben (Lektion):** Konfigurieren Sie die Farben für die "Abschließen" und "Nochmals ansehen" Buttons bequem über eine neue Einstellungsseite im Backend.
- **Plugin Refactoring:** Das Plugin wurde in mehrere Dateien aufgeteilt, um die Wartbarkeit und Erweiterbarkeit zu verbessern.
- **Hierarchischer CSV-Export/Import:** Exportiert nun komplette Kurse zusammen mit zugehörigen Modulen und Lektionen in einer hierarchischen Datei.
- **Verbesserte Kursübersicht:** Kurs-, Modul- und Lektionenstruktur wird nun übersichtlicher angezeigt.

## Anleitung

### Installation
- ZIP hochladen unter WordPress > Plugins > Neu > Plugin hochladen.
- Aktivieren.

### Videolektion erstellen
1. Unter "Videolektionen" > "Neu hinzufügen".
2. Titel, Inhalt und optional einen Video-Link (YouTube/Vimeo) eingeben.
3. Dem Kurs zuweisen (rechte Sidebar). **Wichtig:** Weisen Sie die Lektion dem spezifischen Modul/Ordner innerhalb eines Kurses zu, zu dem sie gehört.

### Kurse und Module strukturieren
- Gehen Sie zu "Videolektionen" > "Kurse".
- Erstellen Sie Top-Level-Begriffe für Ihre Hauptkurse (z.B. "Kurs A", "Kurs B").
- Erstellen Sie Unterbegriffe (Module/Ordner) unter den Hauptkursen (z.B. "Modul 1", "Modul 2" unter "Kurs A").
- Weisen Sie Lektionen den entsprechenden Modulen/Ordnern zu.

### Lektionen sortieren
- Beim Bearbeiten einer Lektion: Feld "Reihenfolge" (Menu Order) ausfüllen. Eine kleinere Zahl bedeutet, dass die Lektion weiter oben angezeigt wird.

### Kursbilder festlegen (per URL)
1. Laden Sie das gewünschte Bild in Ihre WordPress-Medienbibliothek hoch oder nutzen Sie ein bereits hochgeladenes Bild.
2. Klicken Sie auf das Bild in der Medienbibliothek und kopieren Sie die "Datei-URL".
3. Bearbeiten Sie den Kurs unter "Videolektionen" > "Kurse" und fügen Sie die Bild-URL im Feld "Kursbild (Bild-URL)" ein.
4. Speichern Sie den Kurs.

### Kursübersicht Shortcode
- Fügen Sie auf einer Seite den Shortcode `[svl_courses]` ein. Dieser Shortcode zeigt nur die Top-Level-Kurse an, sortiert alphabetisch.

### Fortschritt und Button-Anpassung
- Bei der Lektion wird der Fortschritt und ein Button zur Bestätigung (bzw. Rückgängigmachung) angezeigt.
- Gehen Sie zu "Videolektionen" > "Einstellungen", um die Farben der Buttons anzupassen.

### Export/Import
- Unter dem Admin-Menü "Kurse/Lektionen Export" können Sie die vollständige Kursstruktur (Kurs, Module und Lektionen) als hierarchische CSV-Datei exportieren.
- Über "Kurse/Lektionen Import" können Sie eine hierarchische CSV-Datei importieren, um komplette Kurse einzufügen oder zu aktualisieren.

### Kurs löschen
- Gehen Sie zu "Videolektionen" > "Kurs löschen".
- Wählen Sie den Kurs aus, den Sie löschen möchten.
- Sie erhalten eine Sicherheitsabfrage und die Option, den Kurs vorher zu exportieren.
- Bestätigen Sie die Löschung, um den Kurs, alle zugehörigen Module, Lektionen und Benutzerfortschritte unwiderruflich zu entfernen.
