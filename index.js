/*
 * Copyright IBM Corp. All Rights Reserved.
 *
 * SPDX-License-Identifier: Apache-2.0
 */

'use strict';

const WFHMonitoringContract = require('./lib/assetTransfer');

module.exports.WFHMonitoringContract = WFHMonitoringContract;
module.exports.contracts = [WFHMonitoringContract];
