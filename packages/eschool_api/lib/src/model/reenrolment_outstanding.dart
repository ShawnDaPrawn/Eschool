//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reenrolment_outstanding_items_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_outstanding.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentOutstanding {
  /// Returns a new [ReenrolmentOutstanding] instance.
  ReenrolmentOutstanding({
    required this.contractId,

    required this.version,

    required this.state,

    required this.revision,

    required this.audience,

    required this.confirmed,

    required this.outstanding,

    required this.page,

    required this.items,
  });

  // minimum: 1
  @JsonKey(name: r'contract_id', required: true, includeIfNull: false)
  final int contractId;

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'audience', required: true, includeIfNull: false)
  final int audience;

  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final int confirmed;

  @JsonKey(name: r'outstanding', required: true, includeIfNull: false)
  final int outstanding;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ReenrolmentOutstandingItemsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentOutstanding &&
          other.contractId == contractId &&
          other.version == version &&
          other.state == state &&
          other.revision == revision &&
          other.audience == audience &&
          other.confirmed == confirmed &&
          other.outstanding == outstanding &&
          other.page == page &&
          other.items == items;

  @override
  int get hashCode =>
      contractId.hashCode +
      version.hashCode +
      state.hashCode +
      revision.hashCode +
      audience.hashCode +
      confirmed.hashCode +
      outstanding.hashCode +
      page.hashCode +
      items.hashCode;

  factory ReenrolmentOutstanding.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentOutstandingFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentOutstandingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
