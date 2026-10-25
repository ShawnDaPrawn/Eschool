//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferChoice {
  /// Returns a new [TransferChoice] instance.
  TransferChoice({
    required this.key,

    required this.label,

    required this.detail,
  });

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'detail', required: true, includeIfNull: false)
  final String detail;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferChoice &&
          other.key == key &&
          other.label == label &&
          other.detail == detail;

  @override
  int get hashCode => key.hashCode + label.hashCode + detail.hashCode;

  factory TransferChoice.fromJson(Map<String, dynamic> json) =>
      _$TransferChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$TransferChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
