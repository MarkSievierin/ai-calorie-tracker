<?php

use App\Mcp\Servers\KkalServer;
use Laravel\Mcp\Facades\Mcp;

// Connector URL for claude.ai: https://<host>/mcp?key=<KKAL_TOKEN>
Mcp::web('/mcp', KkalServer::class)->middleware('token');
