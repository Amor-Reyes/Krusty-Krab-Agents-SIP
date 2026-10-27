# Design Patterns Labs — Structural Patterns in Web Application Development

**Course:** Integrative Programming and Technologies  
**Section:** [Your Section]  
**Group:** [Name 1], [Name 2], [Name 3]

---

## Table of Contents

| Lab | Pattern | Folder | Run |
|-----|---------|--------|-----|
| 1 | Adapter — Incompatible File Writers | `lab1_adapter/` | `php lab1_adapter/with_pattern.php` |
| 2 | Bridge — TimesFormatter × DataSource × Compression | `lab2_bridge/` | `php lab2_bridge/with_pattern.php` |
| 3 | Composite — Forum Post/Thread + RAG Document Tree | `lab3_composite/` | `php lab3_composite/with_pattern.php` |
| 4 | Decorator — HTTP Middleware Pipeline | `lab4_decorator/` | `php lab4_decorator/with_pattern.php` |

---

## Lab 1 — Adapter

**Problem:** `ReportExporterWithoutAdapter` branches on `$format` — adding Excel means editing every caller (OCP violation).

**Fix:** `FileWriter` interface + `CsvFileAdapter`, `JsonFileAdapter`, `TextFileAdapter`, `XmlWriterAdapter`. `ReportExporter` depends only on `FileWriter`.

```bash
php lab1_adapter/without_web.php
php lab1_adapter/with_pattern.php
```

---

## Lab 2 — Bridge

**Problem:** `AttendanceJsonGzipReport`, `AttendanceJsonPlainReport`, `AttendanceTextGzipReport`… 2 formats × 2 compressions = 4 classes. Add CSV → +2, add zip → +3. Explosion.

**Fix:** `TimesFormatter` (abstraction) bridges `DataSource` (where data comes from) and `Compressor` (how it's compressed). 6 classes cover all 8 combos.

```bash
php lab2_bridge/without_web.php
php lab2_bridge/with_pattern.php
```

---

## Lab 3 — Composite

**Problem:** `renderNaive()` uses `instanceof` everywhere — adding a `Poll` type means editing the render function.

**Fix:** `ForumComponent` interface with `display()`. `Post` (leaf) and `Thread` (composite) both implement it. RAG variant: `TextComponent` with `getText()` + `embed()`, `Chunk` (leaf), `Section` and `Document` (composites).

```bash
php lab3_composite/without_web.php
php lab3_composite/with_pattern.php
```

---

## Lab 4 — Decorator

**Problem:** `LoggingCachingHttpClient extends LoggingHttpClient` — 3 features = 8 inheritance classes.

**Fix:** `HttpClient` interface + `HttpDecorator` abstract. Stack `LoggingDecorator(CachingDecorator(RetryDecorator(BaseHttpClient)))` at runtime. 4 features = 5 classes.

```bash
php lab4_decorator/without_web.php
php lab4_decorator/with_pattern.php
```

---

## UML Diagrams

| Lab | File |
|-----|------|
| Lab 1 Adapter | `lab1_adapter/lab1_adapter_uml.png` |
| Lab 2 Bridge | `lab2_bridge/lab2_bridge_uml.png` |
| Lab 3 Composite | `lab3_composite/lab3_composite_uml.png` |
| Lab 4 Decorator | `lab4_decorator/lab4_decorator_uml.png` |

---

## References

- Gamma, E., et al. (1994). *Design Patterns: GoF.* Addison-Wesley — Adapter p.139, Bridge p.151, Composite p.163, Decorator p.175.
- Buschmann, F., et al. (1996). *Pattern-Oriented Software Architecture.* Wiley.
