import 'package:eschool_api/eschool_api.dart' as contract;
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/learner_summary.dart';

export 'package:eschool_api/eschool_api.dart'
    show HandleState, GuardianHandleProposal;

abstract interface class HandlesRepository {
  Future<contract.HandleState?> fetch();
  Future<contract.HandleState> choose(String handle);
  Future<List<contract.GuardianHandleProposal>> proposals();
  Future<void> decide(int id, {required bool approve});
}

class ApiHandlesRepository implements HandlesRepository {
  ApiHandlesRepository(this.client);
  final ApiClient client;
  String get ownUrl => '${AppConfig.apiV2Url}me/handle';
  String get proposalsUrl => '${AppConfig.apiV2Url}guardian/handle-proposals';

  @override
  Future<contract.HandleState?> fetch() async {
    final result = await client.send(
      method: 'GET',
      url: ownUrl,
      notFoundAsNull: true,
    );
    return result == null
        ? null
        : contract.HandleState.fromJson(dataMap(result));
  }

  @override
  Future<contract.HandleState> choose(String handle) async {
    final result = await client.send(
      method: 'PUT',
      url: ownUrl,
      data: {'handle': handle},
    );
    return contract.HandleState.fromJson(dataMap(result ?? const {}));
  }

  @override
  Future<List<contract.GuardianHandleProposal>> proposals() async {
    final result = await client.send(method: 'GET', url: proposalsUrl);
    return dataList(
      result ?? const {},
    ).map(contract.GuardianHandleProposal.fromJson).toList();
  }

  @override
  Future<void> decide(int id, {required bool approve}) async {
    await client.send(
      method: 'POST',
      url: '$proposalsUrl/$id',
      data: {'decision': approve ? 'approve' : 'reject'},
    );
  }
}

const handlesFeature = 'handles';
Future<bool> handlesDeployed(HandlesRepository repository) async =>
    await repository.fetch() != null;
