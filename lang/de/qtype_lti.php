<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package qtype_lti
 * @author Amr Hourani amr.hourani@id.ethz.ch, Kristina Isacson kristina.isacson@let.ethz.ch
 * @copyright ETHz 2016 amr.hourani@id.ethz.ch
 */
$string['pluginname'] = 'Externe Verbindung (ETH)';
$string['pluginnamesummary'] = 'Mit der externen Verbindung kann man zu einer, in einem anderen System erstellten Frage, verbinden.';
$string['pluginname_help'] = 'Mittels einer externen Verbindung werden Fragen in anderen Tools in Moodle eingebunden.';
$string['pluginname_link'] = 'question/type/lti';
$string['pluginnameadding'] = 'Eine Verbindung zu einer externen Frage hinzufügen';
$string['pluginnameediting'] = 'Eine Verbindung zu einer externen Frage bearbeiten';
$string['generalfeedback'] = 'Allgemeines Feedback.';
$string['generalfeedback_help'] = 'Allgemeines Feedback wird unabhängig von den gegebenen Antworten angezeigt. Andere Feedbacks sind abhängig von der \'Richtigkeit\' der Antworten.';
$string['stem'] = 'Aufgabentitel';
$string['enterstemhere'] = 'Aufgabentitel einfügen.';
$string['tasktitle'] = 'Aufgabentitel';
$string['maxpoints'] = 'Max Punkte';
$string['accept'] = 'Akzeptieren';
$string['accepted'] = 'Akzeptiert';
$string['accept_grades'] = 'Bewertungen aus dem Tool akzeptieren';
$string['accept_grades_admin'] = 'Bewertungen aus dem Tool akzeptieren';
$string['accept_grades_admin_help'] = 'Legen Sie fest, ob der Tool-Anbieter Bewertungen der Instanzen des Tools hinzufügen, aktualisieren, lesen und löschen darf. Einige Tool-Anbieter ermöglichen, dass Bewertungen aus den Aktivitäten im Tool an Moodle übermittelt werden. Dadurch erscheint das Tool noch besser integriert.';
$string['accept_grades_help'] = 'Hiermit wird spezifiziert, ob der Tool-Anbieter Bewertungen, welche nur mit dieser externen Tool Instanz assoziert sind, hinzufügen, aktualisieren, lesen und löschen kann.

Einige Tool-Anbieter ermöglichen, dass Bewertungen aus den Aktivitäten im Tool an Moodle übermittelt werden. Dadurch erscheint das Tool noch besser integriert.

Bitte beachten, dass diese Einstellung in der Tool Konfiguration überschrieben werden kann.';
$string['action'] = 'Aktion';
$string['activate'] = 'Aktivieren';
$string['activatetoadddescription'] = 'Tool muss aktiviert werden, damit eine Beschreibung hinzugefügt werden kann.';
$string['active'] = 'Aktiv';
$string['activity'] = 'Aktivität';
$string['addnewapp'] = 'Externe Applikation freigeben';
$string['addserver'] = 'Einen vertrauenswürdigen Server hinzufügen';
$string['addtype'] = 'Vorkonfiguriertes Tool hinzufügen';
$string['allow'] = 'Erlauben';
$string['allowinstructorcustom'] = 'Benutzern mit der Rolle "Teacher" das Hinzufügen von benutzerdefinierte Parameter erlauben';
$string['allowsetting'] = 'Dem Tool erlauben 8K von Einstellungen in Moodle zu speichern';
$string['always'] = 'Immer';
$string['automatic'] = 'Automatisch, basierend auf der Tool URL';
$string['autoaddtype'] = 'Tool hinzufügen';
$string['baseurl'] = 'Base URL/tool Registrierungsname';
$string['basiclti'] = 'LTI';
$string['basiclti_base_string'] = 'LTI OAuth base string';
$string['basiclti_endpoint'] = 'LTI Start Endpunkt';
$string['basiclti_in_new_window'] = 'Ihre Aktivität wurde in einem neuen Fenster geöffnet';
$string['basiclti_in_new_window_open'] = 'In einem neuen Fenster öffnen';
$string['basiclti_parameters'] = 'LTI Startparameter';
$string['basicltiactivities'] = 'LTI Aktivitäten';
$string['basicltifieldset'] = 'Custom example fieldset';
$string['basicltiintro'] = 'Beschreibung Aktivität';
$string['basicltiname'] = 'Name Aktivität';
$string['basicltisettings'] = 'Basic Learning Tool Interoperability (LTI) Einstellungen';
$string['cancel'] = 'Abbrechen';
$string['cancelled'] = 'Abgebrochen';
$string['cannot_add'] = 'Sie dürfen nicht ein neues Tool hinzufügen.';
$string['cannot_delete'] = 'Sie dürfen diese Tool Konfiguration nicht löschen.';
$string['cannot_edit'] = 'Sie dürfen diese Tool Konfiguration nicht bearbeiten.';
$string['capabilities'] = 'Funktionalitäten';
$string['capabilitiesrequired'] = 'Dieses Tool braucht auf folgenden Daten Zugriff um aktiviert werden zu können:';
$string['capabilities_help'] = 'Wählen Sie die Funktionalitäten aus, die Sie dem Tool-Anbieter ermöglichen wollen. Es kann mehr als eine Funktionalität ausgewählt werden.';
$string['click_to_continue'] = '<a href="{$a->link}" target="_top">Klicken Sie um weiter zu fahren</a>';
$string['comment'] = 'Kommentar';
$string['configpassword'] = 'Standard Passwort für das externe Tool';
$string['configpreferheight'] = 'Bevorzugte Standardhöhe';
$string['configpreferwidget'] = 'Widget als Standardstart setzen';
$string['configpreferwidth'] = 'Bevorzugte Standardbreite';
$string['configresourceurl'] = 'Standard Ressourcen-URL';
$string['configtoolurl'] = 'Standard URL des externen Tools';
$string['configtypes'] = 'LTI Applikationen aktivieren';
$string['configured'] = 'Konfiguriert';
$string['confirmtoolactivation'] = 'Sind Sie sicher, dass Sie dieses Tool aktivieren wollen?';
$string['contentitem'] = 'Inhaltselement Mitteilung';
$string['contentitem_help'] = 'Falls aktiviert, ist die Option \'Inhalt selektieren\' beim Hinzufügen eines externen Tools verfügbar.';
$string['course_tool_types'] = 'Kurs Tools';
$string['courseactivitiesorresources'] = 'Kurs Aktivitäten oder Material';
$string['courseid'] = 'Kurs ID Nummer';
$string['courseinformation'] = 'Kurs Information';
$string['courselink'] = 'Gehe zum Kurs';
$string['coursemisconf'] = 'Der Kurs ist falsch konfiguriert';
$string['createdon'] = 'Erstellt am';
$string['curllibrarymissing'] = 'Die PHP Curl Bibliothek muss installiert sein um LTI verwenden zu können';
$string['custom'] = 'Angepasste Parameter';
$string['custom_config'] = 'Benutzerdefinierte Tool Konfiguration verwenden.';
$string['custom_help'] = 'Angepasste Parameter sind Einstellungen, die vom Tool-Anbieter genutzt werden. Beispiel: eine Parameter kann die Nutzung einer Ressource des Anbieters sein. Jeder Parameter ist in einer eigenen Zeile einzugeben. Die Eingabe erfolgt nach folgender Regel: "name=value"; z.B. "chapter=3".

Wenn keine direkten Hinweise des Tool-Anbieters vorliegen, ist es am sichersten das Feld leer zu lassen.';
$string['custominstr'] = 'Benutzerdefinierte Parameter';
$string['debuglaunch'] = 'Debug Optionen';
$string['debuglaunchoff'] = 'Normaler Start';
$string['debuglaunchon'] = 'Start debuggen';
$string['default'] = 'Standard';
$string['default_launch_container'] = 'Standard Startcontainer';
$string['default_launch_container_help'] = 'Der Startcontainer beeinflusst die Anzeige des Tools beim Start aus dem Kurs heraus. Einige Startcontainer generieren eine Oberfläche in der Darstellung des Tools, andere passen sich an die Oberfläche von Moodle an.

* **Standard** - Startcontainer wie in Tool-Konfiguration festgelegt verwenden.
* **Einbetten** - Das Tool wird in einem bestehenden Moodle-Fenster angezeigt, ählich wie andere Aktivitäten.
* **Eingebettet, ohne Blöcke** - Das Tool wird in einem bestehenden Moodle-Fenster ohne Blöcke, nur mit der Navigation im Kopf angezeigt.
* **Neues Fenster** - Das Tool öffnet in einem neuen Fenster und
         nutzt den gesamten Raum im Fenster aus. Abhängig vom
         Browser wird ein neuer Tab angelegt oder ein neues Fenster geöffnet.';
$string['delegate'] = 'Benutzern mit der Rolle Teacher delegieren';
$string['delete'] = 'Löschen';
$string['delete_confirmation'] = 'Sind Sie sicher, dass Sie dieses vorkonfigurierte Tool löschen möchten?';
$string['deletetype'] = 'Vorkonfiguriertes Tool löschen';
$string['display_description'] = 'Beim Start Aktivitätsbeschreibung anzeigen';
$string['display_description_help'] = 'Falls aktiviert wird die Aktivitätsbeschreibung (weiter oben spezifiziert) oberhalb des Inhalts des Tool-Anbieters angezeigt.

Die Beschreibung kann dazu verwendet werden zusätzliche Anweisungen zum Starten des Tools bereit zu stellen, muss aber nicht verwendet werden.

Die Beschreibung wird nicht angezeigt wenn der Startcontainer des Tools in einem neuen Fenster geöffnet wird.';
$string['display_name'] = 'Beim Start Aktivitätsname anzeigen';
$string['display_name_help'] = 'Falls aktiviert wird die Aktivitätsname (weiter oben spezifiziert) oberhalb des Inhalts des Tool-Anbieters angezeigt.

Es ist möglich, dass der Tool-Anbieter auch den Titel anzeigt. Diese Option kann verhindern, dass der Aktivitätstitel zwei Mal angezeigt wird.

Der Titel wird nicht angezeigt wenn der Startcontainer des Tools in einem neuen Fenster geöffnet wird.';
$string['domain_mismatch'] = 'Die Domaine der Tool URL entspricht nicht der Tool Konfiguration.';
$string['donot'] = 'Nicht senden';
$string['donotaccept'] = 'Nicht akzeptieren';
$string['donotallow'] = 'Nicht erlauben';
$string['duplicateregurl'] = 'Diese Registrierungs-URL wird bereits verwendet';
$string['editdescription'] = 'Klicken Sie hier um für dieses Tool eine Beschreibung einzufügen';
$string['edittype'] = 'Vorkonfiguriertes Tool bearbeiten';
$string['embed'] = 'Einbetten';
$string['embed_no_blocks'] = 'Eingebettet, ohne Blöcke';
$string['enableemailnotification'] = 'Benachrichtigungs-E-Mails schicken';
$string['enableemailnotification_help'] = 'Falls aktiviert, erhalten Studierende Benachrichtiguns-E-Mails, sobald ihre Eingaben bewertet wurden.';
$string['enterkeyandsecret'] = 'Anwenderschlüssel und Öffentliches Kennwort eingeben';
$string['enterkeyandsecret_help'] = 'Falls Sie einen Anwenderschlüssel und/ oder ein öffentliches Kennwort erhalten haben, bitte hier eingeben';
$string['errorbadurl'] = 'Die URL ist keine valide Tool URL oder Kassette.';
$string['errorincorrectconsumerkey'] = 'Der Anwenderschlüssel ist falsch';
$string['errorinvaliddata'] = 'Ungültige Daten: {$a}';
$string['errorinvalidmediatype'] = 'Ungültiger Medientyp: {$a}';
$string['errorinvalidresponseformat'] = 'Ungültiges Format des Inhaltselements.';
$string['errormisconfig'] = 'Falsch konfiguriertes Tool. Bitten Sie Ihren Moodle Adminstrator die Konfiguration des Tools anzupassen.';
$string['errortooltypenotfound'] = 'LTI Tool Typ nicht gefunden.';
$string['existing_window'] = 'Existierendes Fenster';
$string['extensions'] = 'LTI erweiterte Services';
$string['external_tool_type'] = 'Vorkonfiguriertes Tool';
$string['external_tool_type_help'] = '* **Automatisch, basierend auf der Tool URL** - Die beste Tool Konfiguration wird automatisch ausgewählt. Falls die Tool URL nicht erkannt wird, müssen ev. die Tool Konfigurationsdetails manuell hinzugefügt werden.
* **Ein spezifisch vorkonfiguriertes Tool** - Die Tool Konfiguration für das spezifische Tool wird bei der Kommunikation mit dem externen Tool-Anbieter verwendet. Falls die Tool URL nicht zu einem Tool-Anbieter gehört, wird eine Warnung angezeigt. Es ist nicht immer notwendig eine Tool URL einzugeben.
* **Benutzerdefinierte Konfiguration** - Der Anwenderschlüssel und das öffentliche Kennwort müssen ev. manuell eingefügt werden. Der Anwenderschlüssel und das öffentliche Kennwort können beim Tool-Anbieter bezogen werden. Wobei nicht alle Tools einen Anwenderschlüssel und ein öffentliches Kennwort, in deisem Fall können die Felder leer gelassen werden.

### Bearbeitung vorkonfiguriertes Tool

Drei Icons stehen via vorkonfigurierte Tool Dropdown-Liste zur Verfügung:

* **Hinzufügen** - Auf Kursebene eine Tool Konfiguration erstellen. Alle externen Tool Instanzen in diesem Kurs dürfen die Tool Konfiguration verwenden.
* **Bearbeiten** - Wählen Sie auf Kursebene ein Tool aus der Dropdown-Liste und klicken Sie anschliessend dieses Icon. Die Details der Tool Konfiguration können bearbeitet werden.
* **Löschen** - Auf Kurseben ausgewähltes Tool entfernen.';
$string['external_tool_types'] = 'Vorkonfigurierte Tools';
$string['failedtoconnect'] = 'Moodle konnte nicht mit dem "{$a}" System kommunizieren';
$string['failedtocreatetooltype'] = 'Erstellen eines neuen Tools ist fehlgeschlagen: Kontrollieren Sie die URL und versuchen es nochmals.';
$string['failedtodeletetoolproxy'] = 'Löschen der Tool Registrierung ist fehlgeschlagen. Gehen Sie auf \'Registrierung von externen Tools verwalten\' und löschen Sie sie manuell.';
$string['filter_basiclti_configlink'] = 'Bevorzugte Seiten und deren Passwörter konfigurieren';
$string['filter_basiclti_password'] = 'Ein Passwort ist obligatorisch';
$string['filterconfig'] = 'LTI Administration';
$string['filtername'] = 'LTI';
$string['fixexistingconf'] = 'Eine existierende Konfiguration anstelle der falsch konfigurierten Instanz benutzen';
$string['fixnew'] = 'Neue Konfiguration';
$string['fixnewconf'] = 'Eine neue Konfiguration für die falsch konfigurierte Instanz definieren';
$string['fixold'] = 'Existierende verwenden';
$string['forced_help'] = 'Diese Einstellung wurde auf Kurs Ebene oder Site Ebene in der Tool Konfiguration erzwungen. Sie können sie hier nicht ändern.';
$string['force_ssl'] = 'SSL erzwingen';
$string['force_ssl_help'] = 'Mit dieser Funktion erzwingen Sie SSL für alle Verbindungen zu diesem Anbieter.

Dies gilt auch für alle Web Service Anfrage zu dem Anbieter.

Hierzu ist es erforderelich, dass sowohl das Moodle-System als auch der Anbieter SSL unterstützen. Wenn diese Option aktiviert ist, muss die Verbindung zum Tool-Anbieter zwingend über SSL geschehen.';
$string['generaltool'] = 'Allgemeines Tool';
$string['global_tool_types'] = 'Globale vorkonfigurierte Tools';
$string['grading'] = 'Bewertungen Routing';
$string['icon_url'] = 'Icon URL';
$string['icon_url_help'] = 'Die Funktion Icon URL ermöglicht es, das für die Aktivität verwendete Icon auszutauschen. An Stelle des Standard LTI Icons kann ein Icon, das zur eingebundenen Aktivität passt, gewählt werden.';
$string['id'] = 'id';
$string['invalidid'] = 'Die LTI ID ist falsch';
$string['launch_in_moodle'] = 'Das Tool in Moodle starten';
$string['launch_in_popup'] = 'Das Tool als Popu-Up starten';
$string['launch_url'] = 'Tool URL';
$string['launch_url_help'] = 'Die Tool-URL ist die Webadresse des externen Tools. Sie kann zusätzliche Informationen enthalten, z.B. welche Ressource aufgerufen werden soll. Weitere Informationen gibt der Tool-Anbieter.

Sie können eine Cartridge-URL eingeben, falls Sie darüber verfügen. Anschliessend werden die übrigen Formularfelder automatisch ausgefüllt.

Wenn Sie ein vorkonfigurierten Tool ausgewählt haben, brauchen sie keine Tool-URL einzugeben. Wenn das Tool verwendet wird, um auf das Anbietersystem zuzugreifen, aber nicht eine spezielle Ressource aufrufen soll, dann wird das funktionieren.';
$string['launchinpopup'] = 'Startcontainer';
$string['launchinpopup_help'] = 'Der Startcontainer beeinflusst die Anzeige des Tools beim Start aus dem Kurs heraus. Einige Startcontainer generieren eine Oberfläche in der Darstellung des Tools, andere passen sich an die Oberfläche von Moodle an.

* **Standard** - Startcontainer wie in Tool-Konfiguration festgelegt verwenden.
* **Einbetten** - Das Tool wird in einem bestehenden Moodle-Fenster angezeigt, ählich wie andere Aktivitäten.
* **Eingebettet, ohne Blöcke** - Das Tool wird in einem bestehenden Moodle-Fenster ohne Blöcke, nur mit der Navigation im Kopf angezeigt.
* **Neues Fenster** - Das Tool öffnet in einem neuen Fenster und nutzt den gesamten Raum im Fenster aus. Abhängig vom Browser wird ein neuer Tab angelegt oder ein neues Fenster geöffnet.';
$string['launchoptions'] = 'Startoptionen';
$string['leaveblank'] = 'Leer lassen, falls nicht benötigt';
$string['lti'] = 'LTI';
$string['lti:addinstance'] = 'Neue externe Tool Aktivität hinzufügen';
$string['lti:addcoursetool'] = 'Kursspezifische Tool Konfigurationen hinzufügen';
$string['lti:grade'] = 'Die gelieferten Bewertungen des externen Tools anschauen';
$string['lti:manage'] = 'Instruktor sein, wenn das Tool gestartet wird';
$string['lti:requesttooladd'] = 'Beantragen, dass ein Tool für die gesamte Site konfiguriert ist';
$string['lti:view'] = 'Externes Tool Aktivitäten starten';
$string['ltisettings'] = 'LTI Einstellungen';
$string['lti_administration'] = 'Vorkonfiguriertes Tool bearbeiten';
$string['lti_errormsg'] = 'Das Tool hat folgende Fehlermeldung zurückgegeben: "{$a}"';
$string['lti_launch_error'] = 'Ein Fehler ist beim Start des externen Tools aufgetreten:';
$string['lti_launch_error_tool_request'] = '<p>
Um einen Antrag für die Vervollständigung der Tool Konfiguration beim Administrator zu stellen, klicken Sie <a href="{$a->admin_request_url}" target="_top">hier</a>.
</p>';
$string['lti_launch_error_unsigned_help'] = '<p>Eventuell tritt aufgrund eines fehlenden Anwenderschlüssels oder fehlendes öffentliches Kennwort für den Tool-Anbieter dieser Fehler auf.</p>
<p>Falls Sie einen Anwenderschlüssel und ein öffentliches Kennwort haben, können Sie diese eingeben, wenn Sie die externe Tool Instanz bearbeiten (stellen Sie sicher, dass die erweiterten Optionen sichtbar sind).</p>
<p>Sie können auch<a href="{$a->course_tool_editor}">eine Tool-Anbieter Konfiguration auf Kurs Ebene erstellen</a>.</p>';
$string['lti_tool_request_added'] = 'Die Tool Konfigurationsanfrage wurde erfolgreich abgeschickt. Sie müssen allenfalls Ihren Administrator kontaktieren um die Tool Konfiguration zu vervollständigen.';
$string['lti_tool_request_existing'] = 'Es wurde bereits eine Tool Konfiguration für diese Tool Domain eingereicht.';
$string['ltiunknownserviceapicall'] = 'LTI unbekannter Service API Aufruf.';
$string['main_admin'] = 'Allgemeine Hilfe';
$string['main_admin_help'] = 'Externe Tools ermöglichen es Moodle Nutzern nahtlos mit externen Lernanwendungen zu arbeiten und Daten auszutauschen.
Durch ein spezielles Start-Protokoll erhält das externe Tool Zugriff auf bestimmte Daten des Nutzers.
Dies können sein: Institutionsbezeichnung, Kurs-ID, Nutzer-ID, Nutzername oder E-Mail.

Die Tools auf dieser Seite sind in drei unterschiedliche Typen klassifiziert:

* **Aktiv** - Diese Tool-Anbieter wurden durch einen Administrator geprüft und konfiguriert. Sie können in jedem Kurs genutzt werden.
Wenn ein Anwenderschlüssel und ein Kennwort eingegegeben werden, wird eine geschützte Verbindung zwischen Moodle und dem Tool-Anbieter aufgebaut.
* **Abwarten** - Diese Tool-Anbieter wurden im Rahmen eines Paketimports im System hinterlegt. Sie sind noch nicht von einem Administrator konfiguriert worden.
Teacher können diese Tools verwenden, wenn sie selber über einen Anwenderschlüssel und ein Kennwort verfügen, oder dies nicht erforderlich ist.
* **Gesperrt** - Diese Tool-Anbieter sind vom Administrator nicht zur systemweiten Nutzung freigegeben worden.
Teacher können die Tools dennoch nutzen, wenn sie über einen Anwenderschlüssel und ein Kennwort für dieses Tool verfügen, oder dies nicht erforderlich ist.';
$string['manage_external_tools'] = 'Tools für externe Verbindungen verwalten';
$string['manage_tools'] = 'Vorkonfigurierte Tools für externe Verbindungen verwalten';
$string['manage_tool_proxies'] = 'Registrierung von Tool Proxies für externe Verbindungen verwalten';
$string['manuallyaddtype'] = 'Alternativ können Sie <a href="{$a}">ein Tool manuell konfigurieren</a>.';
$string['miscellaneous'] = 'Verschiedenes';
$string['misconfiguredtools'] = 'Es wurden falsch konfiguriertet Tool Instanzen entdeckt';
$string['missingparameterserror'] = 'Die Seite ist falsch konfiguriert: "{$a}"';
$string['module_class_type'] = 'Moodle Modultyp';
$string['modulename'] = 'Externes Tool';
$string['modulename_help'] = 'Mit der Schnittstelle \'Externe Tools\' geben Sie den Teilnehmer/innen direkten Zugriff auf Lernprogramme ausserhalb von Moodle.

Die Teilnehmer/innen benötigen kein zusätzliches Login. Die Verbindung zwischen Moodle und dem externen Lernprogramm erfolgt über den LTI-Standard, den das andere Lernprogramm unterstützen muss. Sie erhalten vom Anbieter einen Link und Zugangsschlüssel.

Hinweis zum Datenschutz: Häufig werden Namen und E-Mail-Adressen Ihrer Teilnehmer/innen an den externen Anbieter übertragen. Fragen Sie den Anbieter.';
$string['modulename_link'] = 'mod/lti/view';
$string['modulename_shortcut_link'] = 'mod/lti/view/custom';
$string['modulenameplural'] = 'Externe Tools';
$string['modulenamepluralformatted'] = 'LTI Instanzen';
$string['name'] = 'Name';
$string['never'] = 'Nie';
$string['new_window'] = 'Neues Fenster';
$string['no_lti_configured'] = 'Es sind keine aktiven externen Tools konfiguriert.';
$string['no_lti_pending'] = 'Es sind keine wartenden externe Tools vorhanden.';
$string['no_lti_rejected'] = 'Es sind keine zurückgewiesene externe Tools vorhanden.';
$string['no_lti_tools'] = 'Es sind keine externen Tools konfiguriert.';
$string['no_tp_accepted'] = 'Es gibt keine akzeptierten Tool Registrierungen.';
$string['no_tp_cancelled'] = 'Es sind keine abgebrochenen externe Tool Registrierungen vorhanden.';
$string['no_tp_configured'] = 'Es sind keine externen Tools konfiguriert, die nicht registriert wurden.';
$string['no_tp_pending'] = 'Es gibt keine wartende externe Tool Registrierungen.';
$string['no_tp_rejected'] = 'Es gibt keine zurückgewiesenen Registrierungen für externe Tools.';
$string['noattempts'] = 'Es haben keine Versuche auf dieser Tool Instanz stattgefunden';
$string['noltis'] = 'Es gibt keine externen Tool Instanzen';
$string['noprofileservice'] = 'Der Profile Service wurde nicht gefunden';
$string['noservers'] = 'Keine Servers gefunden';
$string['notypes'] = 'Es sind momantan keine LTI Tools in Moodle eingerichtet. Klicken Sie den obigen Link um eines zu installieren.';
$string['noviewusers'] = 'Es wurden keine Benutzer mit den Berechtigungen dieses Tool zu verwenden gefunden';
$string['optionalsettings'] = 'Optionale Einstellungen';
$string['organization'] = 'Organisationsdetails';
$string['organizationdescr'] = 'Organisationsbeschreibung';
$string['organizationid'] = 'Organisation ID';
$string['organizationid_help'] = 'Ein eindeutiged Identifizierungszeichen für diese Moodle Instanz. Typischerweise wird der DNS Name der Organisation verwendet.

Falls dieses Feld leer gelassen wir, wird der Hostname dieser Moodle Site als Standardwert genommen.';
$string['organizationurl'] = 'Organisations-URL';
$string['organizationurl_help'] = 'Die Basis-URL dieser Moodle Instanz.

Falls dieses Feld leer gelassen wird, wird ein Standardwert basierend auf der Site Konfiguration genommen.';
$string['pagesize'] = 'Einreichungen per Seite werden angezeigt';
$string['parameter'] = 'Tool Parameter';
$string['parameter_help'] = 'Tool Parameter sind Einstellungen, welche vom Tool-Anbieter in die akzeptierte Tool Proxy übergeben werden.';
$string['password'] = 'Öffentliches Kennwort';
$string['password_admin'] = 'Öffentliches Kennwort';
$string['password_admin_help'] = 'Das Kennwort wird für die Authentifizierung genutzt. Es wird mit dem Anwenderschlüssel vom Tool-Anbieter zur Verfügung gestellt.
Tools, die keine sichere Datenübertragung oder keine zusätzlichen Dienste (wie Bewertungen) anbieten, können auf Kennwörter verzichten.';
$string['password_help'] = 'Für vorkonfigurierte Tools ist es nicht erforderlich ein Kennwort einzugeben. Dies erfolgte schon während der Tool Konfiguration.

Das Feld ist jedoch auszufüllen, falls eine Verbindung zu einem Anbieter geschaffen werden soll, die noch nicht existiert. Falls das Tool mehrfach genutzt werden soll, ist es zu empfehlen, diese Einstellungen in der Kurs Konfiguration vorzunehmen, um sich mehrfache Arbeit zu ersparen.

Das Kennwort wird für eine Authentifizierung beim externen Tool verwendet. Es wird meist zusammen mit dem Anwenderschlüssel vergeben.

Tools die keine sichere Kommunikation mit Moodle erfordern, benötigen meist auch kein Kennwort.';
$string['pending'] = 'Wartend';
$string['pluginadministration'] = 'Externe Tool Administration';
$string['preferheight'] = 'Bevorzugte Höhe';
$string['preferwidget'] = 'Bevorzugter Widget Start';
$string['preferwidth'] = 'Bevorzugte Weite';
$string['press_to_submit'] = 'Klicken um diese Aktivität zu starten';
$string['privacy'] = 'Datenschutz';
$string['quickgrade'] = 'Rasches Bewerten erlauben';
$string['quickgrade_help'] = 'Falls aktiviert können mehrere Tools auf einer Seite bewertet werden. Bewertungen und Kommentare hinzufügen und dann \'Gesamter Feedback speichern\' klicken um alle Änderungen für diese Seite zu speichern.';
$string['redirect'] = 'Sie werden in wenigen Sekunden weitergeleitet. Falls nicht, klicken Sie bitte den Button.';
$string['register'] = 'Registrieren';
$string['register_warning'] = 'Die Registrierungsseite scheint eine Weile zu brauchen um sich zu öffnen. Falls sie nicht angezeigt wird, überprüfen Sie, ob Sie die richtige URL in den Einstellungen eingegeben haben. Falls Moodle https verwendet, stellen Sie sicher, dass das Tool, welches Sie konfiguriert haben, https unterstützt und dass Sie https in der URL eingetragen haben.';
$string['registertype'] = 'Konfiguration eines neu registrierten externen Tools';
$string['registration_options'] = 'Registierungsoptionen';
$string['registrationname'] = 'Name des Tool-Anbieters';
$string['registrationname_help'] = 'Geben Sie den Namen des Tool-Anbieters an, dessen Angebot Sie registrieren.';
$string['registrationurl'] = 'Registrierungs-URL';
$string['registrationurl_help'] = 'Die Registrierungs-URL wird Ihnen vom Tool-Anbieter zur Verfügung gestellt. An diese URL werden Registrierungsanfragen gesandt.';
$string['reject'] = 'Zurückweisen';
$string['rejected'] = 'Zurückgewiesen';
$string['resource'] = 'Ressource';
$string['resourcekey'] = 'Anwenderschlüssel';
$string['resourcekey_admin'] = 'Anwenderschlüssel';
$string['resourcekey_admin_help'] = 'Der Anwenderschlüssel ist eine Art Nutzername zur Authentifizierung gegenüber dem externen Tool.
Es wird vom Tool-Anbieter vergeben, um das Moodle System eindeutig zu identifizieren.


Der Anwenderschlüssel wird vom Tool-Anbieter bereitgestellt. Dies kann automatisch oder nach Kontaktaufnahme mit dem Anbieter erfolgen.

Tools, die keine sichere Kommunikation von Moodle erfordern oder keine zusätzlichen Dienste (wie Bewertungen) anbieten verzichten häufig auf Anwenderschlüssel.';
$string['resourcekey_help'] = 'Für vorkonfigurierte Tools, ist es nicht notwendig hier einen Ressourcenschlüssel einzufügen, da der Anwenderschlüssel als Teil des Konfigurationsprozesses bereitgestellt wird.

Dieses Feld sollte verwendet werden, falls ein Link zu einem Tool-Anbieter erstellt wird, welcher noch nicht konfiguriert ist.
Falls der Tool-Anbieter mehr als einmal in diesem Kurs verwendet wird, ist es eine gute Idee eine Kurs Tool Konfiguration zu verwenden.

Der Anwenderschlüssel ist eine Art Nutzername zur Authentifizierung gegenüber dem externen Tool.
Er wird vom Tool-Anbieter vergeben, um das Moodle System eindeutig zu identifizieren.

Der Anwenderschlüssel wird vom Tool-Anbieter bereitgestellt. Dies kann automatisch oder nach Kontaktaufnahme mit dem Anbieter erfolgen.

Tools, die keine sichere Kommunikation von Moodle erfordern oder keine zusätzlichen Dienste (wie Bewertungen) anbieten verzichten häufig auf Anwenderschlüssel.';
$string['resourceurl'] = 'Resource URL';
$string['return_to_course'] = 'Klicken Sie  <a href="{$a->link}" target="_top">hier</a> um zurück zum Kurs zu gelangen.';
$string['saveallfeedback'] = 'Feedback speichern';
$string['search:activity'] = 'Exteres Tool - Aktivitätsinformation';
$string['secure_icon_url'] = 'Sichere Icon-URL';
$string['secure_icon_url_help'] = 'Ähnlich wie Icon URL. Die Funktion wird bei verschlüsselter Datenübertragung genutzt, um einen Warnhinweis zu verhindern, falls ein Icon unverschlüsselt aufgerufen wird.';
$string['secure_launch_url'] = 'Sichere Tool-URL';
$string['secure_launch_url_help'] = 'Ähnlich wie die Tool URL, wird aber anstatt der Tool URL verwendet, wenn eine hohe Sicherheit verlangt wird. Moodle benutzt die sichere Tool URL anstelle der Tool URL, wenn die Moodle Site via SSL zugegriffen wird oder wenn die Tool Konfiguration so konfiguriert ist, dass immer via SSL gestartet wird.

Die Tool URL kann auch mit einer https Adresse konfiguriert sein um den Zugriff via SSL zu erzwingen, dann kann dieses Feld leer gelassen werden.';
$string['selectcontent'] = 'Inhalt wählen';
$string['send'] = 'Senden';
$string['services'] = 'Services';
$string['services_help'] = 'Wählen Sie die Services, die an den Tool-Anbieter übertragen werden sollen. Es können mehrere ausgewählt werden.';
$string['setupoptions'] = 'Set-up Optionen';
$string['share_email'] = 'E-Mail des Anwenders an Tool übergeben Hilfe für E-Mail des Anwenders an Tool übergeben ';
$string['share_email_admin'] = 'E-Mail des Anwenders an Tool übergeben Hilfe für E-Mail des Anwenders an Tool übergeben ';
$string['share_email_admin_help'] = 'Legen Sie fest, ob die E-Mail Adresse des Nutzers übergeben werden soll. Dies kann notwendig sein, um bestimmte Informationen im Tool anzuzeigen, oder aufgrund von Aktivitäten im externen Tool E-Mails zu versenden.';
$string['share_email_help'] = 'Legen Sie fest, ob die E-Mail Adresse des Nutzers übergeben werden soll.

Dies kann notwendig sein, um bestimmte Informationen im Tool anzuzeigen, oder aufgrund von Aktivitäten im externen Tool E-Mails zu versenden.

Beachten Sie, dass diese Einstellung in der Tool Konfiguration übersteuert werden kann.';
$string['share_name'] = 'Anwendername an Tool überegeben';
$string['share_name_admin'] = 'Anwendername an Tool überegeben';
$string['share_name_admin_help'] = 'Festlegen, ob der Name des Anwenders an den Anbieter des Tools übergeben werden soll. Dies kann erforderlich sein, um im Tool bestimmte Informationen anzeigen zu können.';
$string['share_name_help'] = 'Festlegen, ob der Name des Anwenders an den Anbieter des Tools übergeben werden soll.

Dies kann erforderlich sein, um im Tool bestimmte Informationen anzeigen zu können.

Beachten Sie, dass diese Einstellung in der Tool Konfiguration übersteuert werden kann.';
$string['share_roster'] = 'Dem Tool erlauben auf den Kursplan zuzugreifen';
$string['share_roster_admin'] = 'Das Tool kann auf den Kursplan zugreifen';
$string['share_roster_admin_help'] = 'Konfigurieren Sie, ob das Tool auf die Liste der eingeschreibenen Benutzer in dem Kurs, von dem das Tool gestartet wird zugreifen darf.';
$string['share_roster_help'] = 'Konfigurieren Sie, ob das Tool auf die Liste der eingeschriebenen Benutzer in dem Kurs zugreifen darf.

Beachten Sie, dass diese Einstellung in der Tool Konfiguration übersteuert werden kann.';
$string['show_in_course_activity_chooser'] = 'In der Aktivitätenauswahl als vorkonfiguriertes Tool anzeigen';
$string['show_in_course_lti1'] = 'Verwendung der Tool Konfiguration';
$string['show_in_course_lti1_help'] = 'Dieses Tool kann für Trainer/innen in der Aktivitätsauswahl angezeigt werden, um es einem Kurs hinzuzufügen. Es kann ebenfalls im vorkonfigurierten Tool-Menü angezeigt werden, wenn ein externes Tool einem Kurs hinzugefügt wird.
Ausserdem kann die Tool-Konfiguration so eingestellt werden, dass sie beim Hinzufügen eines externen Tools zu einem Kurs nur dann verwendet wird, wenn die exakte Tool-URL angegeben wird.';
$string['show_in_course_lti2'] = 'Verwendung der Tool Konfiguration';
$string['show_in_course_lti2_help'] = 'Dieses Tool kann für Trainer/innen in der Aktivitätsauswahl angezeigt werden, um es einem Kurs hinzuzufügen. Es kann ebenfalls im vorkonfigurierten Tool-Menü angezeigt werden, wenn ein externes Tool einem Kurs hinzugefügt wird. .';
$string['show_in_course_no'] = 'Nicht anzeigen; nur benutzen wenn eine passende Tool URL eingetragen ist';
$string['show_in_course_preconfigured'] = 'Als vorkonfiguriertes Tool anzeigen, wenn ein externes Tool hinzugefügt wird';
$string['size'] = 'Grösse Parameter';
$string['submission'] = 'Eingabe';
$string['submissions'] = 'Eingaben';
$string['submissionsfor'] = 'Eingaben für {$a}';
$string['successfullycreatedtooltype'] = 'Ein neues Tool wurde erfolgreich hinzugefügt!';
$string['successfullyfetchedtoolconfigurationfromcontent'] = 'Die Tool Konfiguration wurde erfolreich von dem gewählten Inhalt genommen.';
$string['subplugintype_ltiresource'] = 'LTI Service Ressource';
$string['subplugintype_ltiresource_plural'] = 'LTI Service Ressourcen';
$string['subplugintype_ltiservice'] = 'LTI Service';
$string['subplugintype_ltiservice_plural'] = 'LTI Services';
$string['subplugintype_ltisource'] = 'LTI Quelle';
$string['subplugintype_ltisource_plural'] = 'LTI Quellen';
$string['toggle_debug_data'] = 'Debug-Daten umschalten';
$string['tool_config_not_found'] = 'Für dies URL wurde keine Tool Konfiguration gefunden.';
$string['tool_settings'] = 'Tool Einstellungen';
$string['tooldescription'] = 'Tool-Beschreibung';
$string['tooldescription_help'] = 'Die Beschreibung des Tools, die Teachern in der Aktivitätsliste angezeigt wird.

Sie sollte beschreiben, wofür das Tool ist, was es tut und jede weitere Information enthalten, die der Teacher haben muss.';
$string['toolisbeingused'] = 'Dieses Tool wird  {$a} Mal verwendet';
$string['toolisnotbeingused'] = 'Dieses Tool wurde bisher nicht verwendet.';
$string['toolproxy'] = 'Registrierung von externen Tools';
$string['toolproxy_help'] = 'Administratoren können hier externe Tools registrieren, die den LTI 2.0 Standard bereitstellen. Für den Beginn ist nur eine URL des Anbieters des Tools erforderlich. Die Berechtigungen und Service-Zugänge werden dann beim Anlegen einer neuen Aktivität festgelegt.

Die registrierten Tools werden in vier Kategorien angezeigt:

* **Konfiguriert** - Das Tool wurde angelegt, der Registrierungsprozess wurde jedoch noch nicht begonnen.
* **Wartend ** - Der Registrierungsprozess wurde begonnen, jedoch noch nicht abgeschlossen. Verschieben Sie die Einstellungen zurück zu \'konfiguriert\' und speichern Sie sie erneut.
* **Akzeptiert ** - Die Tool-Einstellungen wurden bestätigt. Die Ressourcen erscheinen unter \'Wartend\'.
* **Zurückgewiesen ** - Die Registrierung wurde vom Anbieter abgewiesen. Prüfen Sie die Einstellungen und verschieben Sie sie zurück in die Kategorie \'Konfiguriert\', damit der Prozess erneut starten kann.';
$string['toolproxyregistration'] = 'Registrierung von externen Tools';
$string['toolregistration'] = 'Registrierung von externen Tools';
$string['toolsetup'] = 'Externes Tool Konfiguration';
$string['tooltypes'] = 'Tools';
$string['tooltypeadded'] = 'Vorkonfiguriertes Tool hinzugefügt';
$string['tooltypedeleted'] = 'Vorkonfiguriertes Tool gelöscht';
$string['tooltypenotdeleted'] = 'Vorkonfiguriertes Tool konnte nicht gelöscht werden';
$string['tooltypeupdated'] = 'Vorkonfiguriertes Tool wurde aktualisiert';
$string['toolurl'] = 'Tool URL';
$string['toolurlplaceholder'] = 'Tool URL...';
$string['toolurl_help'] = 'Die Basis-URL des Tools wird verwendet um die Start URLs mit der korrekten Konfiguration zu verknüpfen. Die Verwendung von http(s) am Beginn ist optional.

Die Basis-URL wird auch verwendet wenn das Tool keine separate Start-URL zur Verfügung stellt.

Die Basis-URL von tool.com passt beispielsweise für folgende URLs:

* tool.com
* tool.com/quizzes
* tool.com/quizzes/quiz.php?id=10
* www.tool.com/quizzes

Die Basis-URL von www.tool.com/quizzes hingegen passt für folgende URLs:

* www.tool.com/quizzes
* tool.com/quizzes
* tool.com/quizzes/take.php?id=10

Die Basis-URL von *quiz.tool.com* hingegen passt für folgende URLs:

* quiz.tool.com
* quiz.tool.com/take.php?id=10

Wenn es zwei unterschiedliche Tool-Konfigurationen für die gleiche Domain gibt, wird die spezifischere benutzt.

Sie können, falls vorhanden, auch eine cartridge URL eintragen. Die Details für das Tool werden dann automatisch eingefügt.';
$string['typename'] = 'Name des Tools';
$string['typename_help'] = 'Die Toolbezeichnung wird genutzt, um den Tool-Anbieter in Moodle zu identifizieren. Die Bezeichnung wird Trainer/innen gezeigt, wenn sie das Tool in ihren Kursen einbinden.';
$string['types'] = 'Typen';
$string['unabletocreatetooltype'] = 'Das Tool konnte nicht erstellt werden';
$string['unabletofindtooltype'] = 'Das Tool konte nicht gefunden werden für {$a->id}';
$string['unknownstate'] = 'Unbekannter Status';
$string['update'] = 'Aktualisieren';
$string['useraccountinformation'] = 'Benutzerkontoinformation';
$string['userpersonalinformation'] = 'Benutzerpersönliche Informartion';
$string['using_tool_cartridge'] = 'Tool Cartridge anwenden';
$string['using_tool_configuration'] = 'Tool Konfiguration anwenden: ';
$string['validurl'] = 'Eine valide URL muss mit http(s):// beginnen';
$string['viewsubmissions'] = 'Eingabe und Bewertungsansicht anzeigen';

$string['privacy:metadata:courseid'] = 'ID des Kurses von dem der Benutzer den LTI Consumer zugreift';
$string['privacy:metadata:courseidnumber'] = 'ID Nummer des Kurses von dem der Benutzer den LTI Consumer zugreift';
$string['privacy:metadata:coursefullname'] = 'Der ganze Name des Kurses von dem der Benutzer den LTI Consumer zugreift';
$string['privacy:metadata:courseshortname'] = 'Der Kurzname des Kurses von dem der Benutzer den LTI Consumer zugreift';
$string['privacy:metadata:createdby'] = 'Benutzer der den Eintrag erstellt hat.';
$string['privacy:metadata:email'] = 'Die E-Mail-Adresse des Benutzers der auf den LTI Consumer zugreift';
$string['privacy:metadata:externalpurpose'] = 'Der LTI Consumer stellt Benutzerinformation und Kontext dem LTI Tool-Anbieter zur Verfügung.';
$string['privacy:metadata:firstname'] = 'Der Vorname des Benutzers der auf den LTI Consumer zugreift';
$string['privacy:metadata:fullname'] = 'Der ganze Name des Benutzers der auf den LTI Consumer zugreift';
$string['privacy:metadata:lastname'] = 'Der Nachname des Benutzers der auf den LTI Consumer zugreift';
$string['privacy:metadata:role'] = 'Die Rolle im Kurs für den Benutzer der auf den LTI Consumer zugreift';
$string['privacy:metadata:userid'] = 'Die ID des Benutzers der auf den LTI Consumer zugreift';
$string['privacy:metadata:useridnumber'] = 'Die ID Nummer des Benutzers der auf den LTI Consumer zugreift';
$string['privacy:metadata:username'] = 'Der Benutzername des Benutzers der auf den LTI Consumer zugreift';
$string['reviewmode'] = 'Im Überprüfungsmodus';
$string['lti:addgloballypreconfigedtoolinstance'] = 'Add a globally preconfigured tool';
$string['lti:adddefaultinstance'] = 'Add a non-globally-configured tool';
$string['lti:admin'] = 'LTI qtype Admin';
$string['lti:backupcourse'] = 'LTI qtype Course Backup by Course ID';
$string['lti:regradelti'] = 'LTI qtype Quiz regrade by Quiz ID';
$string['removerestoredlink'] = 'Remove the LTI tool URL upon restoring';
$string['removerestoredlink_help'] = 'Enabling this option will remove the Tool URL from each question restored. Please note that this option will not remove the tool provider connection.';