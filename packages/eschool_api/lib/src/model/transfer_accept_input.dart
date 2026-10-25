//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_accept_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferAcceptInput {
  /// Returns a new [TransferAcceptInput] instance.
  TransferAcceptInput({
    required this.operationId,

    required this.revision,

    required this.fingerprint,

    required this.sessionYearId,

    required this.classSectionId,

    required this.categoryId,

    required this.confirmation,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  // minimum: 1
  @JsonKey(name: r'category_id', required: true, includeIfNull: false)
  final int categoryId;

  @JsonKey(name: r'confirmation', required: true, includeIfNull: false)
  final bool confirmation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferAcceptInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.fingerprint == fingerprint &&
          other.sessionYearId == sessionYearId &&
          other.classSectionId == classSectionId &&
          other.categoryId == categoryId &&
          other.confirmation == confirmation;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      fingerprint.hashCode +
      sessionYearId.hashCode +
      classSectionId.hashCode +
      categoryId.hashCode +
      confirmation.hashCode;

  factory TransferAcceptInput.fromJson(Map<String, dynamic> json) =>
      _$TransferAcceptInputFromJson(json);

  Map<String, dynamic> toJson() => _$TransferAcceptInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
