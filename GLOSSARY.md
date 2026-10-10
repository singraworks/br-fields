# br-fields

Brazilian personal and company data, such as CPF and CNPJ, and the ways of showing it safely: validating it, writing it in its conventional form, and hiding part of it.

## Language

### Operations

**Mask**:
The display pattern a kind of value is conventionally written in, such as `###.###.###-##` for a CPF. A noun only, never an operation.
_Avoid_: máscara (as a verb), pattern, template

**Format**:
To render a value through its Mask: `12345678909` becomes `123.456.789-09`.
_Avoid_: mask (as a verb), apply the mask

**Redact**:
To hide part of a single value while keeping the shape of its Mask, always hiding any check digits: `***.456.789-**`.
_Avoid_: mask, anonymise, obfuscate

**Scrub**:
To find every recognisable value inside free text, such as a log line, and redact it.
_Avoid_: redact (for text), sanitise
