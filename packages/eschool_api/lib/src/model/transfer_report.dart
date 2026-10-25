//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_report.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferReport {
  /// Returns a new [TransferReport] instance.
  TransferReport({
    required this.id,

    required this.sha256,

    required this.yearName,

    required this.termName,

    required this.releasedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'sha256', required: true, includeIfNull: false)
  final String sha256;

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @JsonKey(name: r'term_name', required: true, includeIfNull: false)
  final String termName;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final DateTime releasedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferReport &&
          other.id == id &&
          other.sha256 == sha256 &&
          other.yearName == yearName &&
          other.termName == termName &&
          other.releasedAt == releasedAt;

  @override
  int get hashCode =>
      id.hashCode +
      sha256.hashCode +
      yearName.hashCode +
      termName.hashCode +
      releasedAt.hashCode;

  factory TransferReport.fromJson(Map<String, dynamic> json) =>
      _$TransferReportFromJson(json);

  Map<String, dynamic> toJson() => _$TransferReportToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
