# moodle-filter_faq
Filter plugin for Moodle to use multilanguage capable FAQ- and helptexts by use of patterns.

The intention of this plugin is to show or link helpful texts in the respective user language based on a repository of helptexts. These helptexts must be organized in a filesystem structure using a folder for each language, in which all files across languages share the same structure. The paths can be used freely. Each helptext (later referred to as 'element') is represented by a folder too, in which several text files must reside:
- version
- shorttitle
- longtitle
- shortdescription
- longdescription

The usage should work by placing a filter-pattern anyhwere on the page with the following options:

`{faq:/folder/folder/element:displaytype}`

The plugin looks for the requested element in the users primary language and English as fallback (if the primary language is not English anyway). By comparing the version number, it is possible to determine if the text is outdated in the primary language. In this case, the user should be notified when the help texts are displayed and be able to view the current English version.

The `displaytype` regulates how the element is displayed and can have the following values:
- collapsiblelonglong
- collapsiblelongshort
- collapsibleshortlong
- collapsibleshortshort
- linkshort
- linklong
- linkurl
- modallonglong
- modallongshort
- modalshortlong
- modalshortshort
- textlong
- textshort
- textshortonly
- titlelong
- titleshort

The plugin also provides a string-API. Strings can be stored in the subfolder "stringlib", in which for each language a separate folder has to be created. Inside this folder files named by the respective moodle component and the extension ".json" can be placed. For example, one would place the file `{$CFG->datadir}/faq/stringlib/en/local_eduportal.json` with the content

```
{
    "roles:localized:emp": "Employee"
}
```

In Moodle, calling `\filter_faq\stringlib::get_string("local_eduportal", "roles:localized:emp")` would return the respective string. If such string is not present, filter_faq will automatically fallback to the Moodle `get_string`-Method. It checks for the existance of the string using Moodle's string manager. If the string does not exist there either, it will only return `[[identifier]]`, e.g. `[[roles:localized:emp]]`.

Further, you can use the stringlib with its short-code, e.g.

```
{faq:stringlib:roles:localized:emp~local_eduportal~Employee}
```

Which basically has the Syntas {faq:stringlib:_textid_~_component_~_defaultvalue_}.

## Specification of files and values
### Files
#### version

The version-file must contain the date and a subversion in the format YYYYmmddvv, e.g. 2023031400 for the first revision of the file created on March 14th 2023.

#### shorttitle

The shorttitle must contain a short title that identifies the page to the user. It should not be longer than 2 or 3 words.

#### longtitle

The longtitle must contain a longer title that identifies the page to the user. It should not be longer than 10 words / one sentence.

#### shortdescription

The shortdescription must contain one paragraph explaining the content of the helptext or providing a short hint for the user to solve a problem or answer a question. It must be plain text and **must not contain HTML**.

#### longdescription

The longdescription can be of any length and should provide the user with answers to his questions or instructions to his problems as comprehensively as possible. It may contain HTML. If it does not contain HTML its contents are enhanced by the use of `nl2br`-function.

### Values
#### displaytype
##### collapsible*

This pattern displays the helptext as a collapsible item using the HTML-elements details and summary. The subtypes 'long' and 'short' regulate if longtitle/shorttitle or longdescription/shortdescription shall be used.

- collapsiblelonglong: use longtitle and longdescription
- collapsiblelongshort: use longtitle and shortdescription
- collapsibleshortlong: use shorttitle and longdescription
- collapsibleshortshort: use shorttitle and shortdescription

##### linkshort

This pattern should create a standardized link to the helptext. It should be based on the following format:

```
<a href="$url" target="_blank" class="btn-link" style="margin: 0 4px;">
    <i class="fa-regular fa-circle-question"></i>
    $shorttitle
</a>
```

##### linklong

This pattern should create a standardized link to the helptext. It should be based on the following format:

```
<a href="$url" target="_blank" class="btn-link" style="margin: 0 4px;">
    <i class="fa-regular fa-circle-question"></i>
    $longtitle
</a>
```

##### linkurl

This pattern only gives the url to the helptext and can be used by the calling entity as desired.

##### modal*

This pattern displays the helptext as in a modal dialog. The subtypes 'long' and 'short' regulate if longtitle/shorttitle or longdescription/shortdescription shall be used.

- modallonglong: use longtitle and longdescription
- modallongshort: use longtitle and shortdescription
- modalshortlong: use shorttitle and longdescription
- modalshortshort: use shorttitle and shortdescription

##### textlong

This pattern displays the contents of the longdescription-textfile. If it contains HTML, it is displayed as is without modification. It it does not contain HTML it is enhanced by the use of the `nl2br`-function.

##### textshort

This pattern displays the contents of the shortdescription-textfile enhancing the text by the use of the `nl2br`-function. It automatically adds a "readmore"-link at the end of the text to the longtext-page.

##### textshortonly

This pattern displays the shortdescription-textfile as described above without the "readmore"-link.

##### titlelong

Only print the longtitle.

##### titleshort

Only print the shorttitle.

### Files within texts

#### Use of the ELEMENTPATH

Files can be placed inside each folder. Any of the text-sources within the same folder
can refer to the respective file using the {{ELEMENTPATH}}-keyword. For example, you have
a folder containing the following files:

```
en/an/article/about/
- attachment.docx
- image.png
- longdescription
- longtitle
- shortdescription
- shorttitle
- version
```

Inside your faq-texts, you can use these files as follows:

```html
<p>
    Please check or our <a href="{{ELEMENTPATH}}/attachment.docx">document</a>
    or view the image:
    <img src="{{ELEMENTPATH}}/image.png" alt="an image" />
</p>
```

Similar to the use of text files, any language missing the respective file
falls back to the file in the primary language. In case English is the primary
language, just place your file in the en-Folder.

#### Use of the GENERALPATH

However, in certain cases you want to use files from another folder.
If files shall be shared across folders, they must be placed inside the folder
`general`, which contains a folder for each language you provide.

To refer such files, you must use the {{GENERALPATH}}-keyword.

In case you have place the files "attachment.docx" and "image.png" in your general-path:

```
general/en/an/article/about/
- attachment.docx
- image.png
```

and further you have an faq-folder

```
de/another/article/
- longdescription
- longtitle
- shortdescription
- shorttitle
- version
```

Inside your faq-texts, you can use these files as follows:

```html
<p>
    Please check or our <a href="{{GENERALPATH}}/an/article/about/attachment.docx">document</a>
    or view the image:
    <img src="{{GENERALPATH}}/an/article/about/image.png" alt="an image" />
</p>
```

As you can see, the full path is used, but only without specifying the language.
Again, the plugin tries to retrieve the file in the users preferred language and
falls back to the primary language.
