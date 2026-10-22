//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'module.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class Module {
  /// Returns a new [Module] instance.
  Module({required this.key, required this.readOnly});

  /// The module's key in config/modules.php, for example `fees`.
  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  /// True when the module was switched off with data left in it, so it can be seen but not changed.
  @JsonKey(name: r'read_only', required: true, includeIfNull: false)
  final bool readOnly;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Module && other.key == key && other.readOnly == readOnly;

  @override
  int get hashCode => key.hashCode + readOnly.hashCode;

  factory Module.fromJson(Map<String, dynamic> json) => _$ModuleFromJson(json);

  Map<String, dynamic> toJson() => _$ModuleToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
